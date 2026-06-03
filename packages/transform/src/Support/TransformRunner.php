<?php

declare(strict_types=1);

namespace Moox\Transform\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;
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

            /** @var Model $destination */
            $destination = $this->resolveDestinationModel($record, $definition, $destinationClass, $payload);
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
            $destination->save();

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
            if ($sourceType === 'db_table' && ($rowKey === null || (is_string($rowKey) && trim($rowKey) === ''))) {
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

        $rowKeys = DB::connection($connection)
            ->table($table)
            ->orderBy($keyColumn)
            ->pluck($keyColumn)
            ->all();

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

        foreach ($references as $index => $reference) {
            if (! is_array($reference)) {
                $warnings[] = "Invalid source reference at index {$index}.";

                continue;
            }

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

        if (! is_string($table) || $table === '' || $rowKey === null || ! is_string($keyColumn) || $keyColumn === '') {
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
     * - mapped path: source.field|map:1=de_DE,2=en_US,*=de_DE
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

        $value = Arr::get($payload, $basePath);

        foreach ($segments as $operation) {
            $value = $this->inlineOperationRegistry->applyOperation($operation, $value, $destinationField, $warnings);
        }

        return $value;
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

        return Arr::has($payload, $basePath);
    }

    /**
     * @param  class-string<Model>  $destinationClass
     * @param  array<string, mixed>  $payload
     */
    private function resolveDestinationModel(
        TransformRecord $record,
        TransformDefinition $definition,
        string $destinationClass,
        array $payload
    ): Model {
        $destination = null;
        if ($record->destination_key !== null && $record->destination_key !== '') {
            $destination = $destinationClass::query()
                ->whereKey($record->destination_key)
                ->first();
        }

        if ($destination instanceof Model) {
            return $destination;
        }

        $destinationMatch = $this->resolveDestinationMatchData($definition, $payload);
        if ($destinationMatch !== []) {
            $query = $destinationClass::query();
            foreach ($destinationMatch as $destinationField => $value) {
                $query->where((string) $destinationField, $value);
            }

            $matched = $query->first();
            if ($matched instanceof Model) {
                return $matched;
            }
        }

        return new $destinationClass;
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
     * @return array<string, mixed>
     */
    private function resolveDestinationMatchData(TransformDefinition $definition, array $payload): array
    {
        $destinationMatch = $this->resolveArrayAttribute($definition, 'destination_match');
        if ($destinationMatch === []) {
            return [];
        }

        $resolved = [];
        $warnings = [];
        foreach ($destinationMatch as $destinationField => $sourcePath) {
            if (! is_string($destinationField) || $destinationField === '' || ! is_string($sourcePath) || $sourcePath === '') {
                continue;
            }

            $value = $this->resolveMappedValue($payload, $sourcePath, $destinationField, $warnings);
            if ($value === null) {
                continue;
            }

            $resolved[$destinationField] = $value;
        }

        return $resolved;
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
}
