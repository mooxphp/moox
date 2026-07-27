<?php

declare(strict_types=1);

namespace Moox\Transform\Support;

use Illuminate\Support\Arr;

final class SourceContextResolver
{
    /**
     * @param  list<array<string, mixed>>  $definitionReferences
     * @param  list<array<string, mixed>>  $runtimeReferences
     * @param  array<string, mixed>  $payload
     * @return array{references: list<array<string, mixed>>, primary_source_id: string|int|null}
     */
    public function resolve(array $definitionReferences, array $runtimeReferences, array $payload): array
    {
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

            $sourceId = $this->resolveReferenceSourceId($reference, $payload);

            $resolvedReferences[] = array_filter([
                'source_type' => $sourceType,
                'connection' => $reference['connection'] ?? null,
                'table' => $reference['table'] ?? null,
                'key_column' => $reference['key_column'] ?? null,
                'path' => $reference['path'] ?? null,
                'url' => $reference['url'] ?? null,
                'alias' => $reference['alias'] ?? null,
                'source_id' => $sourceId,
            ], static fn (mixed $value): bool => $value !== null && $value !== '');
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
     * @param  array{references: list<array<string, mixed>>, primary_source_id: string|int|null}  $sourceContext
     */
    public function formatSummary(array $sourceContext): string
    {
        $references = $sourceContext['references'] ?? [];
        if (! is_array($references) || $references === []) {
            $primarySourceId = $sourceContext['primary_source_id'] ?? null;

            return $primarySourceId !== null
                ? 'source_id='.(string) $primarySourceId
                : 'source_id=unknown';
        }

        $parts = [];
        foreach ($references as $reference) {
            if (! is_array($reference)) {
                continue;
            }

            $sourceType = (string) ($reference['source_type'] ?? 'unknown');
            $sourceId = $reference['source_id'] ?? null;
            $sourceIdString = $sourceId !== null ? (string) $sourceId : 'unknown';

            if ($sourceType === 'db_table') {
                $connection = (string) ($reference['connection'] ?? 'default');
                $table = (string) ($reference['table'] ?? 'unknown');
                $keyColumn = (string) ($reference['key_column'] ?? 'id');
                $parts[] = "db_table:{$connection}.{$table}.{$keyColumn}={$sourceIdString}";

                continue;
            }

            if (in_array($sourceType, ['file_json', 'file_csv'], true)) {
                $path = (string) ($reference['path'] ?? 'unknown');
                $parts[] = "{$sourceType}:{$path}:{$sourceIdString}";

                continue;
            }

            if ($sourceType === 'api') {
                $url = (string) ($reference['url'] ?? 'unknown');
                $parts[] = "api:{$url}:{$sourceIdString}";

                continue;
            }

            if ($sourceType === 'projection') {
                $sourcePath = (string) ($reference['source_path'] ?? 'unknown');
                $parts[] = "projection:{$sourcePath}={$sourceIdString}";

                continue;
            }

            $parts[] = "{$sourceType}:{$sourceIdString}";
        }

        return $parts === [] ? 'source_id=unknown' : implode('; ', $parts);
    }

    /**
     * @param  array{references: list<array<string, mixed>>, primary_source_id: string|int|null}  $sourceContext
     */
    public function sourceReferenceLabel(array $sourceContext): ?string
    {
        foreach ($sourceContext['references'] ?? [] as $reference) {
            if (! is_array($reference)) {
                continue;
            }

            $sourceId = $reference['source_id'] ?? null;
            if ($sourceId === null || $sourceId === '') {
                continue;
            }

            $keyColumn = $reference['key_column'] ?? null;
            if (is_string($keyColumn) && $keyColumn !== '') {
                $alias = is_string($reference['alias'] ?? null) && $reference['alias'] !== ''
                    ? $reference['alias'].'.'
                    : '';

                return "{$alias}{$keyColumn}={$sourceId}";
            }

            $sourcePath = $reference['source_path'] ?? null;
            if (is_string($sourcePath) && $sourcePath !== '') {
                return "{$sourcePath}={$sourceId}";
            }

            return 'source_id='.$sourceId;
        }

        $primarySourceId = $sourceContext['primary_source_id'] ?? null;
        if ($primarySourceId !== null && $primarySourceId !== '') {
            return 'source_id='.$primarySourceId;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $reference
     * @param  array<string, mixed>  $payload
     */
    private function resolveReferenceSourceId(array $reference, array $payload): mixed
    {
        if (array_key_exists('row_key', $reference) && $reference['row_key'] !== null && $reference['row_key'] !== '') {
            return $reference['row_key'];
        }

        $sourceType = $reference['source_type'] ?? null;
        if ($sourceType !== 'db_table') {
            return null;
        }

        $keyColumn = is_string($reference['key_column'] ?? null) && $reference['key_column'] !== ''
            ? $reference['key_column']
            : 'id';
        $alias = is_string($reference['alias'] ?? null) && $reference['alias'] !== ''
            ? $reference['alias']
            : null;

        $candidates = [];
        if ($alias !== null) {
            $candidates[] = "{$alias}.{$keyColumn}";
        }

        $candidates[] = $keyColumn;

        foreach ($candidates as $path) {
            $value = Arr::get($payload, $path);
            if (! $this->isMissingValue($value)) {
                return $value;
            }
        }

        if ($alias !== null) {
            $nested = $payload[$alias] ?? null;
            if (is_array($nested)) {
                if (array_key_exists($keyColumn, $nested) && ! $this->isMissingValue($nested[$keyColumn])) {
                    return $nested[$keyColumn];
                }

                foreach ($nested as $nestedKey => $nestedValue) {
                    if (is_string($nestedKey) && strcasecmp($nestedKey, $keyColumn) === 0 && ! $this->isMissingValue($nestedValue)) {
                        return $nestedValue;
                    }
                }
            }
        }

        foreach ($payload as $topLevelKey => $topLevelValue) {
            if (is_string($topLevelKey) && strcasecmp($topLevelKey, $keyColumn) === 0 && ! $this->isMissingValue($topLevelValue)) {
                return $topLevelValue;
            }
        }

        return null;
    }

    private function isMissingValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        return is_string($value) && trim($value) === '';
    }
}
