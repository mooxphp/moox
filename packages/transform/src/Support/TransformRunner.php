<?php

declare(strict_types=1);

namespace Moox\Transform\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;
use Moox\Transform\Support\Exceptions\TransformDestinationConflictException;
use Moox\Transform\Support\Operations\InlineOperationRegistry;

class TransformRunner
{
    private InlineOperationRegistry $inlineOperationRegistry;

    public function __construct(
        private readonly TransformValidator $validator,
        ?InlineOperationRegistry $inlineOperationRegistry = null
    ) {
        $this->inlineOperationRegistry = $inlineOperationRegistry ?? app(InlineOperationRegistry::class);
    }

    public function run(TransformRecord $record): void
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

            if ($this->expandDbTableIterationRecords($record, $definition)) {
                return;
            }

            $sourceResolution = $this->resolveSourcePayload($record);
            $payload = $sourceResolution['payload'];
            if ($payload === []) {
                $this->markSkipped($record, 'Missing source payload.');

                return;
            }

            $resolved = $this->resolveMappedData($definition, $payload);
            $resolvedData = $resolved['data'];
            $warnings = array_merge(
                $sourceResolution['warnings'],
                $resolved['warnings']
            );

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

            $sourceContext = $this->resolveSourceContext($record, $definition, $payload);
            /** @var Model $prototype */
            $prototype = new $destinationClass;
            $destinationMatch = $this->resolveDestinationMatchData(
                $definition,
                $payload,
                $prototype,
                $destinationClass,
                $sourceContext,
                (int) $record->getKey(),
                (string) $definition->name,
            );

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

            $assignment = $this->assignAttributesWithGracefulDegradation($destination, $resolvedData);

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

    private function expandDbTableIterationRecords(TransformRecord $record, TransformDefinition $definition): bool
    {
        $definitionReferences = $this->resolveArrayAttribute($definition, 'source_references');
        $runtimeReferences = $this->resolveArrayAttribute($record, 'source_references');
        $references = $runtimeReferences !== [] ? $runtimeReferences : $definitionReferences;
        if ($references === []) {
            return false;
        }

        $iterableIndexes = [];
        foreach ($references as $index => $reference) {
            if (! is_array($reference)) {
                continue;
            }

            $sourceType = $reference['source_type'] ?? null;
            $rowKey = $reference['row_key'] ?? null;
            $rowKeyFrom = $reference['row_key_from'] ?? null;
            $hasIterableRowKey = $rowKey !== null && (! is_string($rowKey) || trim($rowKey) !== '');
            $hasDynamicRowKey = is_string($rowKeyFrom) && trim($rowKeyFrom) !== '';
            if ($sourceType === 'db_table' && ! $hasIterableRowKey && ! $hasDynamicRowKey) {
                $iterableIndexes[] = $index;
            }
        }

        if ($iterableIndexes === []) {
            return false;
        }

        if (count($iterableIndexes) > 1) {
            $record->forceFill([
                'status' => 'failed',
                'validation_status' => 'invalid',
                'error_message' => 'Only one db_table source reference can omit row_key for iteration.',
            ])->save();

            return true;
        }

        $targetIndex = $iterableIndexes[0];
        $reference = $references[$targetIndex];
        if (! is_array($reference)) {
            return false;
        }

        $table = $reference['table'] ?? null;
        $keyColumn = $reference['key_column'] ?? 'id';
        $connection = $this->resolveConnectionName($reference['connection'] ?? null);
        if (! is_string($table) || $table === '' || ! is_string($keyColumn) || $keyColumn === '') {
            $record->forceFill([
                'status' => 'failed',
                'validation_status' => 'invalid',
                'error_message' => 'Invalid db_table reference configuration for iteration.',
            ])->save();

            return true;
        }

        $rowKeysQuery = DB::connection($connection)
            ->table($table)
            ->orderBy($keyColumn);

        $this->applyDbTableWhereClauses($rowKeysQuery, $reference);

        $rowKeys = $rowKeysQuery->pluck($keyColumn)->all();

        if ($rowKeys === []) {
            $record->forceFill([
                'status' => 'skipped',
                'validation_status' => 'pending',
                'degraded' => true,
                'error_message' => "No source rows found in [{$table}] for iteration.",
            ])->save();

            return true;
        }

        $failed = 0;
        foreach ($rowKeys as $rowKey) {
            $recordReferences = $references;
            if (! is_array($recordReferences[$targetIndex])) {
                continue;
            }

            $recordReferences[$targetIndex]['row_key'] = $rowKey;
            $iterationRecord = TransformRecord::query()->create([
                'transform_definition_id' => $record->transform_definition_id,
                'source_projection' => $record->source_projection,
                'source_references' => $recordReferences,
            ]);

            $this->run($iterationRecord);
            $status = (string) $iterationRecord->fresh()?->status;
            if (! in_array($status, ['processed', 'updated', 'skipped'], true)) {
                $failed++;
            }
        }

        $record->forceFill([
            'status' => $failed === 0 ? 'processed' : 'failed',
            'validation_status' => $failed === 0 ? 'valid' : 'invalid',
            'degraded' => $failed > 0,
            'error_message' => $failed === 0
                ? 'Expanded iteration into '.count($rowKeys).' transform records.'
                : 'Expanded iteration into '.count($rowKeys)." transform records with {$failed} failures.",
            'last_success_at' => $failed === 0 ? now() : null,
        ])->save();

        return true;
    }

    /**
     * @return array{payload: array<string, mixed>, warnings: array<int, string>, input_hash: string}
     */
    private function resolveSourcePayload(TransformRecord $record): array
    {
        $projection = $this->resolveArrayAttribute($record, 'source_projection');
        $definition = $record->definition;
        $definitionReferences = $definition instanceof TransformDefinition
            ? $this->resolveArrayAttribute($definition, 'source_references')
            : [];
        $runtimeReferences = $this->resolveArrayAttribute($record, 'source_references');
        $references = $runtimeReferences !== [] ? $runtimeReferences : $definitionReferences;
        $warnings = [];
        $merged = $projection;

        foreach ($this->orderReferencesForResolution($references) as $index => $reference) {
            if (! is_array($reference)) {
                $warnings[] = "Invalid source reference at index {$index}.";

                continue;
            }

            $reference = $this->resolveDynamicReference($reference, $merged);
            $sourcePayload = $this->resolveReferencePayload($reference, $warnings);
            if ($sourcePayload === null) {
                continue;
            }

            $alias = $reference['alias'] ?? null;
            if (is_string($alias) && $alias !== '') {
                $merged[$alias] = $sourcePayload;
            }

            $merged = $this->mergePayload($merged, $sourcePayload);
        }

        $normalized = json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return [
            'payload' => $merged,
            'warnings' => $warnings,
            'input_hash' => hash('sha256', (string) $normalized),
        ];
    }

    /**
     * @param  array<string, mixed>  $reference
     * @param  array<int, string>  $warnings
     * @return array<string, mixed>|null
     */
    private function resolveReferencePayload(array $reference, array &$warnings): ?array
    {
        $type = $reference['source_type'] ?? null;
        if (! is_string($type) || $type === '') {
            $warnings[] = 'Missing source_type in source reference.';

            return null;
        }

        return match ($type) {
            'db_table' => $this->resolveDbReferencePayload($reference, $warnings),
            'static' => $this->resolveStaticReferencePayload($reference, $warnings),
            'file_json' => $this->resolveJsonFileReferencePayload($reference, $warnings),
            'file_csv' => $this->resolveCsvFileReferencePayload($reference, $warnings),
            'api' => $this->resolveApiReferencePayload($reference, $warnings),
            default => $this->resolveUnsupportedReferencePayload($type, $warnings),
        };
    }

    /**
     * @param  array<string, mixed>  $reference
     * @param  array<int, string>  $warnings
     * @return array<string, mixed>|null
     */
    private function resolveDbReferencePayload(array $reference, array &$warnings): ?array
    {
        $table = $reference['table'] ?? null;
        $rowKey = $reference['row_key'] ?? null;
        $keyColumn = $reference['key_column'] ?? 'id';
        $connection = $reference['connection'] ?? null;
        $hasRowKey = $rowKey !== null && (! is_string($rowKey) || trim((string) $rowKey) !== '');

        if (! is_string($table) || $table === '' || ! $hasRowKey || ! is_string($keyColumn) || $keyColumn === '') {
            $warnings[] = 'Invalid db_table reference configuration.';

            return null;
        }
        if (str_contains($table, '.')) {
            $warnings[] = 'Schema-qualified table names are not allowed for db_table sources.';

            return null;
        }

        $resolvedConnection = $this->resolveConnectionName($connection);
        $queryBuilder = DB::connection($resolvedConnection);
        $query = $queryBuilder->table($table)->where($keyColumn, $rowKey);
        $this->applyDbTableWhereClauses($query, $reference);
        if (is_array($reference['columns'] ?? null) && $reference['columns'] !== []) {
            $query->select($reference['columns']);
        }

        $row = $query->first();
        if ($row === null) {
            $warnings[] = "No db row found in [{$table}] for [{$keyColumn}={$rowKey}].";

            return null;
        }

        return (array) $row;
    }

    private function resolveConnectionName(mixed $connection): ?string
    {
        if (! is_string($connection) || $connection === '' || $connection === 'db_default') {
            return DB::getDefaultConnection();
        }

        return $connection;
    }

    /**
     * @param  array<string, mixed>  $reference
     * @param  array<int, string>  $warnings
     * @return array<string, mixed>|null
     */
    private function resolveJsonFileReferencePayload(array $reference, array &$warnings): ?array
    {
        $path = $reference['path'] ?? null;
        if (! is_string($path) || $path === '') {
            $warnings[] = 'Invalid file_json reference path.';

            return null;
        }

        if (! File::exists($path)) {
            $warnings[] = "JSON file does not exist at [{$path}].";

            return null;
        }

        $decoded = json_decode((string) File::get($path), true);
        if (! is_array($decoded)) {
            $warnings[] = "JSON file at [{$path}] did not decode to an array.";

            return null;
        }

        return $this->applySelector($decoded, $reference);
    }

    /**
     * @param  array<string, mixed>  $reference
     * @param  array<int, string>  $warnings
     * @return array<string, mixed>|null
     */
    private function resolveCsvFileReferencePayload(array $reference, array &$warnings): ?array
    {
        $path = $reference['path'] ?? null;
        if (! is_string($path) || $path === '') {
            $warnings[] = 'Invalid file_csv reference path.';

            return null;
        }

        if (! File::exists($path)) {
            $warnings[] = "CSV file does not exist at [{$path}].";

            return null;
        }

        $rows = array_map(
            static fn (string $line): array => str_getcsv($line, ',', '"', '\\'),
            file($path)
        );
        $header = array_shift($rows);
        if ($header === null) {
            $warnings[] = "CSV file at [{$path}] has no valid header row.";

            return null;
        }

        $rowKey = $reference['row_key'] ?? null;
        $keyColumn = $reference['key_column'] ?? 'id';
        foreach ($rows as $row) {
            $assoc = array_combine($header, $row);
            if ($rowKey === null || ! is_string($keyColumn) || $keyColumn === '' || (($assoc[$keyColumn] ?? null) == $rowKey)) {
                return $this->applySelector($assoc, $reference);
            }
        }

        $warnings[] = "No matching CSV row found at [{$path}].";

        return null;
    }

    /**
     * @param  array<string, mixed>  $reference
     * @param  array<int, string>  $warnings
     * @return array<string, mixed>|null
     */
    private function resolveApiReferencePayload(array $reference, array &$warnings): ?array
    {
        $url = $reference['url'] ?? null;
        if (! is_string($url) || $url === '') {
            $warnings[] = 'Invalid api reference url.';

            return null;
        }

        $query = is_array($reference['query'] ?? null) ? $reference['query'] : [];
        $response = Http::timeout(15)->get($url, $query);
        if (! $response->successful()) {
            $warnings[] = "API request failed for [{$url}] with status [{$response->status()}].";

            return null;
        }

        $json = $response->json();
        if (! is_array($json)) {
            $warnings[] = "API response at [{$url}] is not an array payload.";

            return null;
        }

        return $this->applySelector($json, $reference);
    }

    /**
     * @param  array<int, string>  $warnings
     */
    private function resolveUnsupportedReferencePayload(string $type, array &$warnings): null
    {
        $warnings[] = "Unsupported source_type [{$type}].";

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $reference
     * @return array<string, mixed>
     */
    private function applySelector(array $payload, array $reference): array
    {
        $selector = $reference['selector'] ?? null;
        if (! is_string($selector) || $selector === '') {
            return $payload;
        }

        $selected = Arr::get($payload, $selector);

        return is_array($selected) ? $selected : [];
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

    /**
     * @param  array<string, mixed>  $left
     * @param  array<string, mixed>  $right
     * @return array<string, mixed>
     */
    private function mergePayload(array $left, array $right): array
    {
        return array_replace_recursive($left, $right);
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
     * @param  array<string, mixed>  $payload
     * @return array{data: array<string, mixed>, warnings: array<int, string>}
     */
    private function resolveMappedData(TransformDefinition $definition, array $payload): array
    {
        $mapping = $this->resolveArrayAttribute($definition, 'field_map');
        if ($mapping === []) {
            return [
                'data' => $payload,
                'warnings' => [],
            ];
        }

        $resolved = [];
        $warnings = [];

        foreach ($mapping as $destinationField => $sourcePath) {
            if (! is_string($sourcePath) || $sourcePath === '') {
                $warnings[] = "Invalid mapping for {$destinationField}.";

                continue;
            }

            $pathExists = $this->sourceExpressionPathExists($payload, $sourcePath);
            $value = $this->resolveMappedValue($payload, $sourcePath, (string) $destinationField, $warnings);
            if (! $pathExists) {
                $warnings[] = "Mapped source path [{$sourcePath}] was not found.";
            }

            $resolved[(string) $destinationField] = $value;
        }

        return [
            'data' => $resolved,
            'warnings' => $warnings,
        ];
    }

    /**
     * Supported source expression syntax:
     * - plain path: source.field
     * - payload path: coalesce:source.a,source.b or any_truthy:source.a,source.b
     * - piped ops: source.field|map:1=a,2=b,*=c|upper
     *
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $warnings
     */
    private function resolveMappedValue(array $payload, string $sourceExpression, string $destinationField, array &$warnings): mixed
    {
        $segments = array_values(array_filter(array_map('trim', explode('|', $sourceExpression))));
        if ($segments === []) {
            return null;
        }

        $basePath = array_shift($segments);
        if (! is_string($basePath) || $basePath === '') {
            return null;
        }

        $value = $this->resolveSourceBaseValue($payload, $basePath, $destinationField, $warnings);

        foreach ($segments as $operation) {
            $value = $this->inlineOperationRegistry->applyOperation(
                $operation,
                $value,
                $destinationField,
                $warnings,
                $payload,
            );
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $warnings
     */
    private function resolveSourceBaseValue(
        array $payload,
        string $basePath,
        string $destinationField,
        array &$warnings,
    ): mixed {
        if ($this->inlineOperationRegistry->isPayloadBaseExpression($basePath)) {
            return $this->inlineOperationRegistry->applyOperation(
                $basePath,
                null,
                $destinationField,
                $warnings,
                $payload,
            );
        }

        return Arr::get($payload, $basePath);
    }

    /**
     * Check whether the base path of a source expression exists in payload.
     *
     * @param  array<string, mixed>  $payload
     */
    private function sourceExpressionPathExists(array $payload, string $sourceExpression): bool
    {
        $segments = array_values(array_filter(array_map('trim', explode('|', $sourceExpression))));
        if ($segments === []) {
            return false;
        }

        $basePath = array_shift($segments);
        if (! is_string($basePath) || $basePath === '') {
            return false;
        }

        return $this->inlineOperationRegistry->isPayloadBaseExpression($basePath)
            ? $this->inlineOperationRegistry->payloadBaseExpressionExists($payload, $basePath)
            : Arr::has($payload, $basePath);
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
     * @param  array<string, mixed>  $payload
     * @return array{references: list<array<string, mixed>>, primary_source_id: string|int|null}
     */
    private function resolveSourceContext(TransformRecord $record, TransformDefinition $definition, array $payload): array
    {
        $definitionReferences = $this->resolveArrayAttribute($definition, 'source_references');
        $runtimeReferences = $this->resolveArrayAttribute($record, 'source_references');
        $references = $runtimeReferences !== [] ? $runtimeReferences : $definitionReferences;

        $resolvedReferences = [];

        foreach ($references as $reference) {
            if (! is_array($reference)) {
                continue;
            }

            $sourceType = $reference['source_type'] ?? null;
            if (! is_string($sourceType) || $sourceType === '') {
                continue;
            }

            $sourceId = $reference['row_key'] ?? null;
            if ($sourceId === null && $sourceType === 'db_table') {
                $keyColumn = is_string($reference['key_column'] ?? null) && $reference['key_column'] !== ''
                    ? $reference['key_column']
                    : 'id';
                $alias = is_string($reference['alias'] ?? null) && $reference['alias'] !== ''
                    ? $reference['alias'].'.'
                    : '';
                $sourceId = Arr::get($payload, $alias.$keyColumn) ?? Arr::get($payload, $keyColumn);
            }

            $resolvedReferences[] = array_filter([
                'source_type' => $sourceType,
                'connection' => $reference['connection'] ?? null,
                'table' => $reference['table'] ?? null,
                'key_column' => $reference['key_column'] ?? null,
                'path' => $reference['path'] ?? null,
                'url' => $reference['url'] ?? null, // why url isn that spezific
                'alias' => $reference['alias'] ?? null,
                'source_id' => $sourceId,
            ], static fn (mixed $value): bool => $value !== null && $value !== '');
        }

        if ($resolvedReferences === []) {
            $warnings = [];
            foreach ($this->resolveArrayAttribute($definition, 'destination_match') as $destinationField => $sourcePath) {
                if (! is_string($destinationField) || $destinationField === '' || ! is_string($sourcePath) || $sourcePath === '') {
                    continue;
                }

                $sourceId = $this->resolveMappedValue($payload, $sourcePath, $destinationField, $warnings);
                if ($this->isMissingDestinationMatchValue($sourceId)) {
                    continue;
                }

                $resolvedReferences[] = [
                    'source_type' => 'projection',
                    'source_path' => $sourcePath,
                    'source_id' => $sourceId,
                ];
            }
        }

        $primarySourceId = null;
        foreach ($resolvedReferences as $reference) {
            if (($reference['source_id'] ?? null) !== null) {
                $primarySourceId = $reference['source_id'];

                break;
            }
        }

        return [
            'references' => $resolvedReferences,
            'primary_source_id' => $primarySourceId,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array{references: list<array<string, mixed>>, primary_source_id: string|int|null}  $sourceContext
     * @return array<string, mixed>
     */
    private function resolveDestinationMatchData(
        TransformDefinition $definition,
        array $payload,
        Model $prototype,
        string $destinationClass,
        array $sourceContext,
        int $transformRecordId,
        string $transformDefinitionName,
    ): array {
        $destinationMatch = $this->resolveArrayAttribute($definition, 'destination_match');
        if ($destinationMatch === []) {
            return [];
        }

        $resolved = [];
        $missing = [];
        $warnings = [];

        foreach ($destinationMatch as $destinationField => $sourcePath) {
            if (! is_string($destinationField) || $destinationField === '' || ! is_string($sourcePath) || $sourcePath === '') {
                $missing[] = (string) $destinationField;

                continue;
            }

            $value = $this->resolveMappedValue($payload, $sourcePath, $destinationField, $warnings);
            if ($this->isMissingDestinationMatchValue($value)) {
                $missing[] = "{$destinationField} (from {$sourcePath})";

                continue;
            }

            $resolved[$destinationField] = $this->normalizeDestinationMatchValue($prototype, $destinationField, $value);
        }

        if ($missing !== []) {
            throw TransformDestinationConflictException::incompleteDestinationMatch(
                $missing,
                $destinationMatch,
                $sourceContext,
                $destinationClass,
                $transformRecordId,
                $transformDefinitionName,
            );
        }

        return $resolved;
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
        $errorCode = (string) $exception->getCode();
        if (in_array($errorCode, ['23000', '23505'], true)) {
            return true;
        }

        $message = strtolower($exception->getMessage());

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

    private function normalizeDestinationMatchValue(Model $prototype, string $field, mixed $value): mixed
    {
        $castType = strtolower(explode(':', (string) ($prototype->getCasts()[$field] ?? ''))[0]);

        return match ($castType) {
            'int', 'integer' => (int) $value,
            'float', 'double', 'decimal', 'real' => (float) $value,
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'array', 'json', 'collection', 'object' => $value,
            default => is_scalar($value) ? (string) $value : $value,
        };
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
    private function assignAttributesWithGracefulDegradation(Model $destination, array $resolvedData): array
    {
        $fillable = $destination->getFillable();
        $translated = $this->resolveTranslatedAttributes($destination);
        $allowed = $fillable !== [] ? array_flip(array_merge($fillable, $translated)) : null;
        $warnings = [];
        $ignored = [];
        $locale = $this->resolveTranslationLocale($resolvedData);

        foreach ($resolvedData as $field => $value) {
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

    /**
     * @param  array<int, mixed>  $references
     * @return array<int, mixed>
     */
    private function orderReferencesForResolution(array $references): array
    {
        $static = [];
        $withRowKey = [];
        $withRowKeyFrom = [];

        foreach ($references as $reference) {
            if (! is_array($reference)) {
                $withRowKey[] = $reference;

                continue;
            }

            $sourceType = $reference['source_type'] ?? null;
            $rowKey = $reference['row_key'] ?? null;
            $rowKeyFrom = $reference['row_key_from'] ?? null;
            $hasRowKey = $rowKey !== null && (! is_string($rowKey) || trim((string) $rowKey) !== '');

            if ($sourceType === 'static') {
                $static[] = $reference;

                continue;
            }

            if ($hasRowKey) {
                $withRowKey[] = $reference;

                continue;
            }

            if (is_string($rowKeyFrom) && trim($rowKeyFrom) !== '') {
                $withRowKeyFrom[] = $reference;

                continue;
            }

            $withRowKey[] = $reference;
        }

        return array_values(array_merge($static, $withRowKey, $withRowKeyFrom));
    }

    /**
     * @param  array<string, mixed>  $reference
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function resolveDynamicReference(array $reference, array $payload): array
    {
        $rowKey = $reference['row_key'] ?? null;
        $hasRowKey = $rowKey !== null && (! is_string($rowKey) || trim((string) $rowKey) !== '');

        if ($hasRowKey) {
            return $reference;
        }

        $rowKeyFrom = $reference['row_key_from'] ?? null;
        if (! is_string($rowKeyFrom) || trim($rowKeyFrom) === '') {
            return $reference;
        }

        $reference['row_key'] = Arr::get($payload, $rowKeyFrom);

        return $reference;
    }

    /**
     * @param  Builder  $query
     * @param  array<string, mixed>  $reference
     */
    private function applyDbTableWhereClauses($query, array $reference): void
    {
        $where = $reference['where'] ?? null;
        if (! is_array($where)) {
            return;
        }

        foreach ($where as $clause) {
            if (! is_array($clause)) {
                continue;
            }

            $column = $clause['column'] ?? null;
            $operator = strtolower((string) ($clause['operator'] ?? '='));

            if (! is_string($column) || $column === '') {
                continue;
            }

            if ($operator === 'null') {
                $query->whereNull($column);

                continue;
            }

            if ($operator === 'not_null') {
                $query->whereNotNull($column);

                continue;
            }

            if ($operator === 'in' && is_array($clause['value'] ?? null)) {
                $query->where(function ($nested) use ($column, $clause): void {
                    foreach ($clause['value'] as $value) {
                        if ($value === null) {
                            $nested->orWhereNull($column);
                        } else {
                            $nested->orWhere($column, $value);
                        }
                    }
                });

                continue;
            }

            if (array_key_exists('value', $clause)) {
                $query->where($column, $operator, $clause['value']);

                continue;
            }

            $query->where($column, $operator);
        }
    }

    /**
     * @param  array<string, mixed>  $reference
     * @param  array<int, string>  $warnings
     * @return array<string, mixed>|null
     */
    private function resolveStaticReferencePayload(array $reference, array &$warnings): ?array
    {
        $data = $reference['data'] ?? null;
        if (! is_array($data)) {
            $warnings[] = 'Invalid static source reference data.';

            return null;
        }

        return $data;
    }
}
