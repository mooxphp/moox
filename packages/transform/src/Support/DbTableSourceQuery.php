<?php

declare(strict_types=1);

namespace Moox\Transform\Support;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class DbTableSourceQuery
{
    /**
     * @param  array<string, mixed>  $reference
     */
    public static function table(?string $connection, array $reference): Builder
    {
        $table = $reference['table'] ?? null;

        if (! is_string($table) || $table === '') {
            throw new \InvalidArgumentException('db_table reference requires a table name.');
        }

        return DB::connection(self::resolveConnectionName($connection))->table($table);
    }

    /**
     * @param  array<string, mixed>  $reference
     */
    public static function applyWhereClauses(Builder $query, array $reference): void
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
                $query->where(function (Builder $nested) use ($column, $clause): void {
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

    public static function hasRowKey(mixed $rowKey): bool
    {
        return $rowKey !== null && (! is_string($rowKey) || trim($rowKey) !== '');
    }

    public static function hasRowKeyFrom(mixed $rowKeyFrom): bool
    {
        return is_string($rowKeyFrom) && trim($rowKeyFrom) !== '';
    }

    public static function resolveConnectionName(?string $connection): Connection|string
    {
        if ($connection === null || $connection === '' || $connection === 'db_default') {
            return DB::getDefaultConnection();
        }

        return $connection;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function normalizeRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if (! is_string($value)) {
                continue;
            }

            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $row[$key] = $decoded;
            }
        }

        return $row;
    }

    /**
     * @return iterable<int, list<array<string, mixed>>>
     */
    public static function orderedChunk(Builder $query, string $keyColumn, int $chunkSize): iterable
    {
        $chunkSize = max(1, $chunkSize);
        $offset = 0;

        do {
            $rows = (clone $query)
                ->orderBy($keyColumn)
                ->offset($offset)
                ->limit($chunkSize)
                ->get()
                ->map(static fn (object $row): array => self::normalizeRow((array) $row))
                ->values()
                ->all();

            if ($rows === []) {
                break;
            }

            yield $rows;
            $offset += count($rows);
        } while (count($rows) === $chunkSize);
    }
}
