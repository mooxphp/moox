<?php

declare(strict_types=1);

namespace Moox\Transform\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;

final class SourcePayloadResolver
{
    public function __construct(
        private readonly ConfiguredImportRecordPayloadReader $importRecordPayloadReader,
        private readonly TemplateValueResolver $templateValueResolver,
    ) {
    }

    /**
     * @return array{payload: array<string, mixed>, warnings: array<int, string>, input_hash: string}
     */
    public function resolve(TransformRecord $record, TransformDefinition $definition): array
    {
        $projection = $this->arrayAttribute($record, 'source_projection');

        if ($record->parent_transform_record_id !== null) {
            $normalized = json_encode($projection, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return [
                'payload' => $projection,
                'warnings' => [],
                'input_hash' => hash('sha256', (string) $normalized),
            ];
        }

        $definitionReferences = $this->arrayAttribute($definition, 'source_references');
        $runtimeReferences = $this->arrayAttribute($record, 'source_references');
        $references = $runtimeReferences !== [] ? $runtimeReferences : $definitionReferences;
        $warnings = [];
        $merged = $projection;

        foreach ($this->orderReferencesForResolution($references) as $index => $reference) {
            if (! is_array($reference)) {
                $warnings[] = "Invalid source reference at index {$index}.";

                continue;
            }

            $reference = $this->resolveDynamicReference($reference, $merged);
            $sourcePayload = $this->resolveReferencePayload($reference, $merged, $warnings);
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
     * @param  array<string, mixed>  $context
     * @param  array<int, string>  $warnings
     * @return array<string, mixed>|null
     */
    private function resolveReferencePayload(array $reference, array $context, array &$warnings): ?array
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
            'api_import_record' => $this->resolveImportRecordReferencePayload($reference, $context, $warnings),
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

        if (! is_string($table) || $table === '' || ! DbTableSourceQuery::hasRowKey($rowKey) || ! is_string($keyColumn) || $keyColumn === '') {
            $warnings[] = 'Invalid db_table reference configuration.';

            return null;
        }

        if (str_contains($table, '.')) {
            $warnings[] = 'Schema-qualified table names are not allowed for db_table sources.';

            return null;
        }

        $query = DbTableSourceQuery::table(is_string($connection) ? $connection : null, $reference)
            ->where($keyColumn, $rowKey);

        if (is_array($reference['columns'] ?? null) && $reference['columns'] !== []) {
            $query->select($reference['columns']);
        }

        $row = $query->first();
        if ($row === null) {
            $warnings[] = "No db row found in [{$table}] for [{$keyColumn}={$rowKey}].";

            return null;
        }

        return DbTableSourceQuery::normalizeRow((array) $row);
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<int, string>  $warnings
     * @return array<string, mixed>|null
     */
    private function resolveImportRecordReferencePayload(array $reference, array $context, array &$warnings): ?array
    {
        $rowKey = $reference['row_key'] ?? null;
        $rowKeyFrom = $reference['row_key_from'] ?? null;

        $recordId = $this->templateValueResolver->resolve($reference['record_id'] ?? null, $context);
        if (! is_numeric($recordId)) {
            $warnings[] = 'Invalid api_import_record reference record_id.';

            return null;
        }

        try {
            $payload = $this->importRecordPayloadReader->read((int) $recordId);
        } catch (\Throwable $throwable) {
            $warnings[] = $throwable->getMessage();

            return null;
        }

        $itemKey = $reference['item_key'] ?? null;

        if (DbTableSourceQuery::hasRowKey($rowKey) && is_string($itemKey) && $itemKey !== '') {
            $items = $this->normalizeImportRecordList($payload, $reference);
            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                if ((string) Arr::get($item, $itemKey) === (string) $rowKey) {
                    return $item;
                }
            }

            $warnings[] = "No import record list item found for [{$itemKey}={$rowKey}].";

            return null;
        }

        if (DbTableSourceQuery::hasRowKey($rowKey) || DbTableSourceQuery::hasRowKeyFrom($rowKeyFrom)) {
            $warnings[] = 'Import record row_key could not be resolved from payload.';

            return null;
        }

        if (! is_array($payload)) {
            $warnings[] = 'Import record payload is not an array.';

            return null;
        }

        if (array_is_list($payload)) {
            return null;
        }

        return $payload;
    }

    /**
     * @param  array<mixed>  $payload
     * @param  array<string, mixed>  $reference
     * @return list<array<string, mixed>>
     */
    private function normalizeImportRecordList(array $payload, array $reference): array
    {
        $selector = $reference['selector'] ?? null;
        if (is_string($selector) && $selector !== '') {
            $selected = Arr::get($payload, $selector);

            return is_array($selected) && array_is_list($selected)
                ? array_values(array_filter($selected, is_array(...)))
                : (is_array($selected) ? [$selected] : []);
        }

        if (array_is_list($payload)) {
            return array_values(array_filter($payload, is_array(...)));
        }

        return [$payload];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeDbRow(array $row): array
    {
        return DbTableSourceQuery::normalizeRow($row);
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
     * @param  array<string, mixed>  $left
     * @param  array<string, mixed>  $right
     * @return array<string, mixed>
     */
    private function mergePayload(array $left, array $right): array
    {
        return array_replace_recursive($left, $right);
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
            $hasRowKey = DbTableSourceQuery::hasRowKey($rowKey);

            if ($sourceType === 'static') {
                $static[] = $reference;

                continue;
            }

            if ($hasRowKey) {
                $withRowKey[] = $reference;

                continue;
            }

            if (DbTableSourceQuery::hasRowKeyFrom($rowKeyFrom)) {
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
        if (DbTableSourceQuery::hasRowKey($reference['row_key'] ?? null)) {
            return $reference;
        }

        $rowKeyFrom = $reference['row_key_from'] ?? null;
        if (! DbTableSourceQuery::hasRowKeyFrom($rowKeyFrom)) {
            return $reference;
        }

        $reference['row_key'] = Arr::get($payload, (string) $rowKeyFrom);

        return $reference;
    }

    /**
     * @return array<string, mixed>
     */
    private function arrayAttribute(TransformRecord|TransformDefinition $model, string $attribute): array
    {
        $value = $model->getAttribute($attribute);

        return is_array($value) ? $value : [];
    }
}
