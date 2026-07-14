<?php

declare(strict_types=1);

namespace Moox\Transform\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Moox\Transform\Enums\TransformExecutionMode;
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;
use Moox\Transform\Support\Exceptions\TransformDestinationConflictException;
use Moox\Transform\Support\Execution\BatchDestinationWriterRegistry;
use Moox\Transform\Support\Execution\BulkItemResult;
use Moox\Transform\Support\Execution\BulkTransformExecutor;
use Moox\Transform\Support\Execution\BulkTransformSummaryFormatter;
use Moox\Transform\Support\Execution\ResolvedTransformDataFactory;
use Moox\Transform\Support\Expansion\ExpandTransformExecutor;
use Moox\Transform\Support\Expansion\TransformProjectionExpander;

class TransformRunner
{
    public function __construct(
        private readonly TransformValidator $validator,
        private readonly SourcePayloadResolver $sourcePayloadResolver,
        private readonly TransformProjectionExpander $projectionExpander,
        private readonly ExpandTransformExecutor $expandExecutor,
        private readonly BulkTransformExecutor $bulkExecutor,
        private readonly ResolvedTransformDataFactory $resolvedTransformDataFactory,
        private readonly BatchDestinationWriterRegistry $batchDestinationWriterRegistry,
    ) {}

    public function run(TransformRecord $record): void
    {
        $this->runSingle($record, allowExpansion: true);
    }

    public function runSingle(TransformRecord $record, bool $allowExpansion = false): void
    {
        $record->forceFill([
            'status' => 'processing',
            'last_run_at' => now(),
            'attempts' => $record->attempts + 1,
            'error_message' => null,
        ])->save();

        try {
            $definition = $record->definition;
            if (! $definition instanceof TransformDefinition || ! $definition->is_active) {
                $record->forceFill([
                    'status' => 'failed',
                    'validation_status' => 'invalid',
                    'error_message' => 'Transform definition missing or inactive.',
                ])->save();

                return;
            }

            if ($this->resolveArrayAttribute($definition, 'destination_match') === []) {
                $record->forceFill([
                    'status' => 'failed',
                    'validation_status' => 'invalid',
                    'error_message' => 'Transform definition requires destination_match to prevent duplicate records on re-import.',
                ])->save();

                return;
            }

            if ($allowExpansion && $record->parent_transform_record_id === null && $this->runExpansionIfNeeded($record, $definition)) {
                return;
            }

            $sourceResolution = $this->sourcePayloadResolver->resolve($record, $definition);
            $payload = $sourceResolution['payload'];
            if ($payload === []) {
                $this->markSkipped($record, 'Missing source payload.');

                return;
            }

            $warnings = $sourceResolution['warnings'];

            /** @var class-string<Model> $destinationClass */
            $destinationClass = $definition->destination_model;
            if (! class_exists($destinationClass)) {
                $record->forceFill([
                    'status' => 'failed',
                    'validation_status' => 'invalid',
                    'error_message' => 'Destination model class does not exist.',
                ])->save();

                return;
            }

            $resolvedRow = $this->resolvedTransformDataFactory->make(
                $definition,
                $payload,
                $sourceResolution['input_hash'],
                $record,
            );
            $warnings = array_merge($warnings, $resolvedRow->warnings);
            $resolvedData = $resolvedRow->resolvedData;
            $sourceContext = $resolvedRow->sourceContext;
            $destinationMatch = $resolvedRow->destinationMatch;

            /** @var Model $destination */
            $destination = $this->resolveDestinationModel(
                $record,
                $destinationClass,
                $destinationMatch,
                $sourceContext,
                (int) $record->getKey(),
                (string) $definition->name,
            );
            $record->destination_key = $destination->getKey() !== null ? (string) $destination->getKey() : $record->destination_key;
            $resolvedData = $this->normalizeResolvedDataForDestinationCasts($destination, $resolvedData);
            $validationRules = $this->resolveValidationRules($definition, $destination, $resolvedData);
            $validation = $this->validator->validate($resolvedData, $validationRules);
            if (! $validation['passes']) {
                $record->forceFill([
                    'status' => 'failed_validation',
                    'validation_status' => 'invalid',
                    'validation_errors' => $validation['errors'],
                    'warnings' => $warnings,
                    'degraded' => false,
                    'error_message' => 'Validation failed.',
                ])->save();

                return;
            }

            $isExistingDestination = $destination->exists;
            if ($isExistingDestination && $this->hasUnchangedInput($record, $sourceResolution['input_hash'])) {
                $record->forceFill([
                    'status' => 'skipped',
                    'validation_status' => 'valid',
                    'validation_errors' => [],
                    'input_hash' => $sourceResolution['input_hash'],
                    'warnings' => $warnings,
                    'degraded' => false,
                    'last_success_at' => now(),
                    'error_message' => null,
                ])->save();

                return;
            }

            $assignment = $this->assignAttributesWithGracefulDegradation($destination, $resolvedData, $isExistingDestination);

            try {
                $destination->save();
            } catch (QueryException $exception) {
                if (! $this->isUniqueConstraintViolation($exception)) {
                    throw $exception;
                }

                $conflictingKeys = $this->findDestinationKeysByMatch($destinationClass, $destinationMatch);

                if (count($conflictingKeys) > 1) {
                    throw TransformDestinationConflictException::multipleMatches(
                        $destinationClass,
                        $destinationMatch,
                        $conflictingKeys,
                        $sourceContext,
                        (int) $record->getKey(),
                        (string) $definition->name,
                    );
                }

                $existingDestinationKey = $conflictingKeys[0]
                    ?? $this->findConflictingDestinationKey($destinationClass, $resolvedData);

                throw TransformDestinationConflictException::uniqueConstraintViolation(
                    $destinationClass,
                    $destinationMatch,
                    $sourceContext,
                    $existingDestinationKey !== null ? (string) $existingDestinationKey : null,
                    $exception->getMessage(),
                    (int) $record->getKey(),
                    (string) $definition->name,
                );
            }

            $isDegraded = $warnings !== [] || $assignment['ignored'] !== [];
            $record->forceFill([
                'status' => $isExistingDestination ? 'updated' : 'processed',
                'validation_status' => 'valid',
                'validation_errors' => [],
                'input_hash' => $sourceResolution['input_hash'],
                'destination_key' => (string) $destination->getKey(),
                'warnings' => array_values(array_filter(array_merge(
                    $warnings,
                    $assignment['warnings']
                ))),
                'degraded' => $isDegraded,
                'last_success_at' => now(),
                'error_message' => null,
            ])->save();
        } catch (TransformDestinationConflictException $exception) {
            $record->forceFill([
                'status' => 'failed',
                'validation_status' => 'invalid',
                'error_message' => $exception->getMessage(),
                'validation_errors' => [
                    'destination_conflict' => $exception->context(),
                ],
            ])->save();
        } catch (\Throwable $throwable) {
            $record->forceFill([
                'status' => 'failed',
                'validation_status' => 'invalid',
                'error_message' => $throwable->getMessage(),
            ])->save();
        }
    }

    private function runExpansionIfNeeded(TransformRecord $record, TransformDefinition $definition): bool
    {
        $iterationError = $this->validateIterableSourceReferences($record, $definition);
        if ($iterationError !== null) {
            $record->forceFill([
                'status' => 'failed',
                'validation_status' => 'invalid',
                'error_message' => $iterationError,
            ])->save();

            return true;
        }

        $mode = TransformExecutionMode::tryFromConfig($definition->execution_mode ?? null);
        $processor = fn (TransformRecord $child): mixed => $this->runSingle($child);

        if ($mode === TransformExecutionMode::Bulk && $this->shouldRunBulkExpansion($record, $definition)) {
            $chunkSize = $this->resolveBulkChunkSize($definition);
            $projectionChunks = $this->projectionExpander->expandInChunks($record, $definition, $chunkSize);

            $hasAnyProjection = false;
            foreach ($projectionChunks as $chunk) {
                $hasAnyProjection = true;
                break;
            }

            if (! $hasAnyProjection) {
                $record->forceFill([
                    'status' => 'skipped',
                    'validation_status' => 'pending',
                    'degraded' => true,
                    'error_message' => 'No source projections found for bulk transform.',
                ])->save();

                return true;
            }

            $projectionChunks = $this->projectionExpander->expandInChunks($record, $definition, $chunkSize);

            $inlineProcessor = fn (array $projection): BulkItemResult => $this->processProjectionInline($definition, $projection);
            $batchProcessor = fn (array $chunk): array => $this->processProjectionBatch($definition, $chunk);

            $this->bulkExecutor->run($record, $definition, $projectionChunks, $processor, $inlineProcessor, $batchProcessor);

            return true;
        }

        $projections = $this->projectionExpander->expand($record, $definition);

        if ($mode === TransformExecutionMode::Bulk && $this->shouldRunBulkExpansion($record, $definition, $projections)) {
            if ($projections === []) {
                $record->forceFill([
                    'status' => 'skipped',
                    'validation_status' => 'pending',
                    'degraded' => true,
                    'error_message' => 'No source projections found for bulk transform.',
                ])->save();

                return true;
            }

            $this->bulkExecutor->run($record, $definition, $projections, $processor);

            return true;
        }

        if ($mode === TransformExecutionMode::Expand) {
            return $this->expandExecutor->run($record, $definition, $processor);
        }

        if ($mode === TransformExecutionMode::Single) {
            $projections = $this->projectionExpander->expand($record, $definition);
            if (count($projections) > 1 || $this->shouldRunBulkExpansion($record, $definition, $projections)) {
                return $this->expandExecutor->run($record, $definition, $processor);
            }
        }

        return false;
    }

    private function shouldRunBulkExpansion(
        TransformRecord $record,
        TransformDefinition $definition,
        ?array $projections = null,
    ): bool {
        $mode = TransformExecutionMode::tryFromConfig($definition->execution_mode ?? null);

        if ($mode === TransformExecutionMode::Bulk && $this->hasIterableSourceReference($record, $definition)) {
            return true;
        }

        if (is_array($projections) && count($projections) > 1) {
            return true;
        }

        $expand = $definition->getAttribute('expand');

        return is_array($expand) && $expand !== [];
    }

    private function hasIterableSourceReference(TransformRecord $record, TransformDefinition $definition): bool
    {
        $definitionReferences = $this->resolveArrayAttribute($definition, 'source_references');
        $runtimeReferences = $this->resolveArrayAttribute($record, 'source_references');
        $references = $runtimeReferences !== [] ? $runtimeReferences : $definitionReferences;

        foreach ($references as $reference) {
            if (! is_array($reference)) {
                continue;
            }

            $sourceType = $reference['source_type'] ?? null;
            if (! in_array($sourceType, ['db_table', 'api_import_record'], true)) {
                continue;
            }

            if (
                ! DbTableSourceQuery::hasRowKey($reference['row_key'] ?? null)
                && ! DbTableSourceQuery::hasRowKeyFrom($reference['row_key_from'] ?? null)
            ) {
                return true;
            }
        }

        $expand = $definition->getAttribute('expand');

        return is_array($expand) && $expand !== [];
    }

    private function validateIterableSourceReferences(TransformRecord $record, TransformDefinition $definition): ?string
    {
        $definitionReferences = $this->resolveArrayAttribute($definition, 'source_references');
        $runtimeReferences = $this->resolveArrayAttribute($record, 'source_references');
        $references = $runtimeReferences !== [] ? $runtimeReferences : $definitionReferences;

        $iterableCount = 0;

        foreach ($references as $reference) {
            if (! is_array($reference)) {
                continue;
            }

            $sourceType = $reference['source_type'] ?? null;
            if (! in_array($sourceType, ['db_table', 'api_import_record'], true)) {
                continue;
            }

            if (
                ! DbTableSourceQuery::hasRowKey($reference['row_key'] ?? null)
                && ! DbTableSourceQuery::hasRowKeyFrom($reference['row_key_from'] ?? null)
            ) {
                $iterableCount++;
            }
        }

        if ($iterableCount > 1) {
            return 'Only one iterable source reference can omit row_key for iteration.';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $resolvedData
     * @return array<string, mixed>
     */
    private function resolveValidationRules(TransformDefinition $definition, Model $destination, array $resolvedData): array
    {
        $modelRules = $this->inferRulesFromModel($destination, $resolvedData);
        $definitionRules = $this->resolveArrayAttribute($definition, 'validation_rules');

        return array_merge($modelRules, $definitionRules);
    }

    /**
     * @param  array<string, mixed>  $resolvedData
     * @return array<string, mixed>
     */
    private function inferRulesFromModel(Model $destination, array $resolvedData): array
    {
        $fillable = $destination->getFillable();
        $fillableMap = $fillable !== [] ? array_flip($fillable) : null;
        $casts = $destination->getCasts();
        $rules = [];

        foreach ($resolvedData as $field => $value) {
            if (is_array($fillableMap) && ! array_key_exists($field, $fillableMap)) {
                continue;
            }

            $castType = strtolower((string) ($casts[$field] ?? ''));
            $castType = explode(':', $castType)[0];

            $fieldRules = ['nullable'];
            if ($value === null) {
                $rules[$field] = $fieldRules;

                continue;
            }

            if (in_array($castType, ['int', 'integer'], true)) {
                $fieldRules[] = 'integer';
            } elseif (in_array($castType, ['float', 'double', 'decimal', 'real'], true)) {
                $fieldRules[] = 'numeric';
            } elseif (in_array($castType, ['bool', 'boolean'], true)) {
                $fieldRules[] = 'boolean';
            } elseif (in_array($castType, ['array', 'json', 'collection', 'object'], true)) {
                $fieldRules[] = 'array';
            } elseif (str_contains($castType, 'date')) {
                $fieldRules[] = 'date';
            } else {
                if (is_int($value)) {
                    $fieldRules[] = 'integer';
                } elseif (is_float($value)) {
                    $fieldRules[] = 'numeric';
                } elseif (is_bool($value)) {
                    $fieldRules[] = 'boolean';
                } elseif (is_array($value)) {
                    $fieldRules[] = 'array';
                } else {
                    $fieldRules[] = 'string';
                }
            }

            $rules[$field] = $fieldRules;
        }

        return $rules;
    }

    private function markSkipped(TransformRecord $record, string $reason): void
    {
        $record->forceFill([
            'status' => 'skipped',
            'validation_status' => 'pending',
            'error_message' => $reason,
            'degraded' => true,
        ])->save();
    }

    /**
     * @param  class-string<Model>  $destinationClass
     * @param  array<string, mixed>  $destinationMatch
     * @param  array{references: list<array<string, mixed>>, primary_source_id: string|int|null}  $sourceContext
     */
    private function resolveDestinationModel(
        TransformRecord $record,
        string $destinationClass,
        array $destinationMatch,
        array $sourceContext,
        int $transformRecordId,
        string $transformDefinitionName,
    ): Model {
        if ($record->destination_key !== null && $record->destination_key !== '') {
            $destination = $this->findDestinationByKey($destinationClass, $record->destination_key);
            if ($destination instanceof Model) {
                return $destination;
            }
        }

        return DB::transaction(function () use (
            $destinationClass,
            $destinationMatch,
            $sourceContext,
            $transformRecordId,
            $transformDefinitionName,
        ): Model {
            $matchedKeys = $this->findDestinationKeysByMatch($destinationClass, $destinationMatch, lock: true);

            if (count($matchedKeys) > 1) {
                throw TransformDestinationConflictException::multipleMatches(
                    $destinationClass,
                    $destinationMatch,
                    $matchedKeys,
                    $sourceContext,
                    $transformRecordId,
                    $transformDefinitionName,
                );
            }

            if (count($matchedKeys) === 1) {
                $destination = $this->findDestinationByKey($destinationClass, (string) $matchedKeys[0]);
                if ($destination instanceof Model) {
                    if ($this->usesSoftDeletes($destinationClass) && $destination->trashed()) {
                        $destination->restore();
                    }

                    return $destination;
                }
            }

            return new $destinationClass;
        });
    }

    /**
     * @param  class-string<Model>  $destinationClass
     */
    private function findDestinationByKey(string $destinationClass, string $key): ?Model
    {
        $query = $destinationClass::query()->whereKey($key);
        if ($this->usesSoftDeletes($destinationClass)) {
            $query->withTrashed();
        }

        $destination = $query->first();

        return $destination instanceof Model ? $destination : null;
    }

    /**
     * @param  class-string<Model>  $destinationClass
     * @param  array<string, mixed>  $destinationMatch
     * @return list<string|int>
     */
    private function findDestinationKeysByMatch(string $destinationClass, array $destinationMatch, bool $lock = false): array
    {
        /** @var Model $prototype */
        $prototype = new $destinationClass;
        $keyName = $prototype->getKeyName();

        $query = $destinationClass::query()->select($keyName);
        if ($this->usesSoftDeletes($destinationClass)) {
            $query->withTrashed();
        }

        foreach ($destinationMatch as $destinationField => $value) {
            $query->where((string) $destinationField, $value);
        }

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->pluck($keyName)
            ->map(static fn (mixed $key): string|int => is_int($key) ? $key : (string) $key)
            ->values()
            ->all();
    }

    /**
     * @param  class-string<Model>  $destinationClass
     */
    private function usesSoftDeletes(string $destinationClass): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($destinationClass), true);
    }

    private function hasUnchangedInput(TransformRecord $record, string $inputHash): bool
    {
        if ($record->destination_key === null || $record->destination_key === '') {
            return false;
        }

        $latestRecord = TransformRecord::query()
            ->where('transform_definition_id', $record->transform_definition_id)
            ->where('destination_key', $record->destination_key)
            ->where('id', '!=', $record->id)
            ->whereIn('status', ['processed', 'updated', 'skipped'])
            ->latest('id')
            ->first();

        return $latestRecord instanceof TransformRecord && $latestRecord->input_hash === $inputHash;
    }

    /**
     * @param  class-string<Model>  $destinationClass
     * @param  array<string, mixed>  $resolvedData
     */
    private function findConflictingDestinationKey(string $destinationClass, array $resolvedData): ?string
    {
        /** @var Model $prototype */
        $prototype = new $destinationClass;
        $keyName = $prototype->getKeyName();

        foreach ($resolvedData as $field => $value) {
            if (! is_string($field) || $field === '' || $this->isMissingDestinationMatchValue($value)) {
                continue;
            }

            $query = $destinationClass::query()->select($keyName);
            if ($this->usesSoftDeletes($destinationClass)) {
                $query->withTrashed();
            }

            $keys = $query->where($field, $value)->pluck($keyName)->all();
            if (count($keys) === 1) {
                return (string) $keys[0];
            }
        }

        return null;
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        if (str_contains($message, 'cannot be null')
            || str_contains($message, 'not null constraint')
            || str_contains($message, 'may not be null')) {
            return false;
        }

        $errorCode = (string) $exception->getCode();
        if (in_array($errorCode, ['23000', '23505'], true)) {
            return str_contains($message, 'duplicate')
                || str_contains($message, 'unique');
        }

        return str_contains($message, 'unique constraint failed')
            || str_contains($message, 'duplicate entry')
            || str_contains($message, 'duplicate key value')
            || str_contains($message, 'unique violation');
    }

    private function isMissingDestinationMatchValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        return is_string($value) && trim($value) === '';
    }

    /**
     * @param  array<string, mixed>  $resolvedData
     * @return array<string, mixed>
     */
    private function normalizeResolvedDataForDestinationCasts(Model $destination, array $resolvedData): array
    {
        $casts = $destination->getCasts();

        foreach ($resolvedData as $field => $value) {
            if (! is_string($value)) {
                continue;
            }

            $castType = strtolower((string) ($casts[$field] ?? ''));
            $castType = explode(':', $castType)[0];
            if (! in_array($castType, ['array', 'json', 'collection', 'object'], true)) {
                continue;
            }

            $decoded = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                continue;
            }

            $resolvedData[$field] = $decoded;
        }

        return $resolvedData;
    }

    /**
     * @param  array<string, mixed>  $resolvedData
     * @return array{ignored: array<int, string>, warnings: array<int, string>}
     */
    private function assignAttributesWithGracefulDegradation(
        Model $destination,
        array $resolvedData,
        bool $isExistingDestination = false,
    ): array {
        $fillable = $destination->getFillable();
        $translated = $this->resolveTranslatedAttributes($destination);
        $allowed = $fillable !== [] ? array_flip(array_merge($fillable, $translated)) : null;
        $warnings = [];
        $ignored = [];
        $locale = $this->resolveTranslationLocale($resolvedData);
        $skipNullAssignments = $isExistingDestination && (bool) config('transform.graceful_degradation', true);

        foreach ($resolvedData as $field => $value) {
            if ($skipNullAssignments && $this->isMissingDestinationMatchValue($value)) {
                continue;
            }

            if (is_array($allowed) && ! array_key_exists($field, $allowed)) {
                $ignored[] = $field;
                $warnings[] = "Ignored unmapped model attribute [{$field}].";

                continue;
            }

            if (in_array($field, $translated, true) && method_exists($destination, 'translateOrNew')) {
                $destination->translateOrNew($locale)->setAttribute($field, $value);

                continue;
            }

            $destination->setAttribute($field, $value);
        }

        return [
            'ignored' => $ignored,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, mixed>  $resolvedData
     */
    private function resolveTranslationLocale(array $resolvedData): string
    {
        $defaultLocale = (string) config('transform.default_locale', app()->getLocale());
        $rawLocale = $resolvedData['locale'] ?? $defaultLocale;

        if (is_string($rawLocale) && $rawLocale !== '') {
            return $rawLocale;
        }

        return $defaultLocale;
    }

    public function processProjectionInline(TransformDefinition $definition, array $projection): BulkItemResult
    {
        try {
            /** @var class-string<Model> $destinationClass */
            $destinationClass = $definition->destination_model;
            if (! class_exists($destinationClass)) {
                return new BulkItemResult('failed', 'Destination model class does not exist.');
            }

            $resolvedRow = $this->resolvedTransformDataFactory->make(
                $definition,
                $projection,
                hash('sha256', (string) json_encode($projection, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            );

            $record = new TransformRecord([
                'transform_definition_id' => $definition->id,
            ]);

            /** @var Model $destination */
            $destination = $this->resolveDestinationModel(
                $record,
                $destinationClass,
                $resolvedRow->destinationMatch,
                $resolvedRow->sourceContext,
                0,
                (string) $definition->name,
            );

            $validationRules = $this->resolveValidationRules($definition, $destination, $resolvedRow->resolvedData);
            $validation = $this->validator->validate($resolvedRow->resolvedData, $validationRules);
            if (! $validation['passes']) {
                return new BulkItemResult('failed_validation', 'Validation failed.');
            }

            $isExistingDestination = $destination->exists;
            $assignment = $this->assignAttributesWithGracefulDegradation($destination, $resolvedRow->resolvedData, $isExistingDestination);
            $destination->save();

            return new BulkItemResult(
                status: $isExistingDestination ? 'updated' : 'processed',
                errorMessage: $assignment['warnings'] !== [] ? implode("\n", $assignment['warnings']) : null,
                destinationKey: $destination->getKey() !== null ? (string) $destination->getKey() : null,
            );
        } catch (TransformDestinationConflictException $exception) {
            return new BulkItemResult('failed', $exception->getMessage());
        } catch (\Throwable $throwable) {
            return new BulkItemResult('failed', $throwable->getMessage());
        }
    }

    /**
     * @param  list<array<string, mixed>>  $projections
     * @return list<BulkItemResult>
     */
    public function processProjectionBatch(TransformDefinition $definition, array $projections): array
    {
        if ($projections === []) {
            return [];
        }

        /** @var class-string<Model> $destinationClass */
        $destinationClass = $definition->destination_model;
        if (! class_exists($destinationClass)) {
            return array_fill(0, count($projections), new BulkItemResult('failed', 'Destination model class does not exist.'));
        }

        $validRows = [];
        $results = [];
        $existingByIndex = [];
        /** @var Model $prototype */
        $prototype = new $destinationClass;
        $destinationMatch = $this->resolveArrayAttribute($definition, 'destination_match');

        foreach ($projections as $index => $projection) {
            $sourceLabel = BulkTransformSummaryFormatter::projectionSourceLabel($projection, $destinationMatch);

            try {
                $resolvedRow = $this->resolvedTransformDataFactory->make(
                    $definition,
                    $projection,
                    hash('sha256', (string) json_encode($projection, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
                );
                $validationRules = $this->resolveValidationRules($definition, $prototype, $resolvedRow->resolvedData);
                $validation = $this->validator->validate($resolvedRow->resolvedData, $validationRules);
                if (! $validation['passes']) {
                    $results[$index] = new BulkItemResult(
                        'failed_validation',
                        'Validation failed.',
                        sourceLabel: $sourceLabel,
                    );

                    continue;
                }

                $existingByIndex[$index] = $this->destinationExistsByMatch($destinationClass, $resolvedRow->destinationMatch);
                $validRows[$index] = $resolvedRow;
            } catch (TransformDestinationConflictException $exception) {
                $results[$index] = new BulkItemResult('failed', $exception->getMessage(), sourceLabel: $sourceLabel);
            } catch (\Throwable $throwable) {
                $results[$index] = new BulkItemResult('failed', $throwable->getMessage(), sourceLabel: $sourceLabel);
            }
        }

        if ($validRows === []) {
            ksort($results);

            return array_values($results);
        }

        $writer = $this->batchDestinationWriterRegistry->resolve($destinationClass, $definition);
        if ($writer === null) {
            foreach ($validRows as $index => $row) {
                $results[$index] = $this->processProjectionInline($definition, $projections[$index]);
            }

            ksort($results);

            return array_values($results);
        }

        try {
            $destinationKeys = $writer->write($destinationClass, $definition, array_values($validRows));
        } catch (\Throwable $throwable) {
            foreach (array_keys($validRows) as $index) {
                $results[$index] = new BulkItemResult(
                    'failed',
                    $throwable->getMessage(),
                    sourceLabel: BulkTransformSummaryFormatter::projectionSourceLabel(
                        $projections[$index],
                        $destinationMatch,
                    ),
                );
            }

            ksort($results);

            return array_values($results);
        }
        $orderedIndexes = array_keys($validRows);

        foreach ($orderedIndexes as $position => $index) {
            $results[$index] = new BulkItemResult(
                status: ($existingByIndex[$index] ?? false) ? 'updated' : 'processed',
                destinationKey: $destinationKeys[$position] ?? null,
            );
        }

        ksort($results);

        return array_values($results);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveArrayAttribute(Model $model, string $attribute): array
    {
        $value = $model->getAttribute($attribute);

        return is_array($value) ? $value : [];
    }

    /**
     * @return array<int, string>
     */
    private function resolveTranslatedAttributes(Model $destination): array
    {
        if (method_exists($destination, 'getTranslatedAttributes')) {
            $attributes = $destination->getTranslatedAttributes();

            return is_array($attributes) ? array_values($attributes) : [];
        }

        if (property_exists($destination, 'translatedAttributes') && is_array($destination->translatedAttributes ?? null)) {
            return array_values($destination->translatedAttributes);
        }

        return [];
    }

    private function resolveBulkChunkSize(TransformDefinition $definition): int
    {
        $bulk = $this->resolveArrayAttribute($definition, 'bulk');
        $configured = $bulk;
        if (is_array($bulk['source'] ?? null) && array_key_exists('chunk_size', $bulk['source'])) {
            return max(1, (int) $bulk['source']['chunk_size']);
        }

        return max(1, (int) ($configured['chunk_size'] ?? config('transform.bulk.chunk_size', 100)));
    }

    /**
     * @param  class-string<Model>  $destinationClass
     * @param  array<string, mixed>  $destinationMatch
     */
    private function destinationExistsByMatch(string $destinationClass, array $destinationMatch): bool
    {
        $query = $destinationClass::query();
        if ($this->usesSoftDeletes($destinationClass)) {
            $query->withTrashed();
        }

        foreach ($destinationMatch as $field => $value) {
            $query->where($field, $value);
        }

        return $query->exists();
    }
}
