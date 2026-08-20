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

            self::applyWhereClause($query, $clause);
        }
    }

    /**
     * @param  array<string, mixed>  $clause
     */
    private static function applyWhereClause(Builder $query, array $clause, bool $or = false): void
    {
        $operator = strtolower((string) ($clause['operator'] ?? '='));

        if ($operator === 'raw' && is_array($clause['value'] ?? null)) {
            $sql = $clause['value']['sql'] ?? null;
            $bindings = $clause['value']['bindings'] ?? [];

            if (! is_string($sql) || $sql === '') {
                return;
            }

            if (! is_array($bindings)) {
                $bindings = [];
            }

            $or ? $query->orWhereRaw($sql, $bindings) : $query->whereRaw($sql, $bindings);

            return;
        }

        if ($operator === 'or' && is_array($clause['value'] ?? null)) {
            $callback = function (Builder $nested) use ($clause): void {
                $subClauses = array_values(array_filter(
                    $clause['value'],
                    static fn (mixed $subClause): bool => is_array($subClause),
                ));

                foreach ($subClauses as $index => $subClause) {
                    if ($index === 0) {
                        self::applyWhereClause($nested, $subClause);

                        continue;
                    }

                    $nested->orWhere(function (Builder $inner) use ($subClause): void {
                        self::applyWhereClause($inner, $subClause);
                    });
                }
            };

            if ($or) {
                $query->orWhere($callback);
            } else {
                $query->where($callback);
            }

            return;
        }

        $column = $clause['column'] ?? null;
        if (! is_string($column) || $column === '') {
            return;
        }

        if ($operator === 'null') {
            $or ? $query->orWhereNull($column) : $query->whereNull($column);

            return;
        }

        if ($operator === 'not_null') {
            $or ? $query->orWhereNotNull($column) : $query->whereNotNull($column);

            return;
        }

        if ($operator === 'in' && is_array($clause['value'] ?? null)) {
            $callback = function (Builder $nested) use ($column, $clause): void {
                foreach ($clause['value'] as $value) {
                    if ($value === null) {
                        $nested->orWhereNull($column);
                    } else {
                        $nested->orWhere($column, $value);
                    }
                }
            };

            $or ? $query->orWhere($callback) : $query->where($callback);

            return;
        }

        if ($operator === 'not_in_subquery' && is_array($clause['value'] ?? null)) {
            $subquery = $clause['value'];
            $subTable = $subquery['table'] ?? null;
            $subColumn = $subquery['column'] ?? null;

            if (! is_string($subTable) || $subTable === '' || ! is_string($subColumn) || $subColumn === '') {
                return;
            }

            $callback = function (Builder $sub) use ($subTable, $subColumn, $subquery): void {
                $sub->from($subTable)->select($subColumn);
                self::applyWhereClauses($sub, ['where' => $subquery['where'] ?? []]);
            };

            $or ? $query->orWhereNotIn($column, $callback) : $query->whereNotIn($column, $callback);

            return;
        }

        if ($operator === 'datetime_gte' && array_key_exists('value', $clause)) {
            $or ? $query->orWhere($column, '>=', $clause['value']) : $query->where($column, '>=', $clause['value']);

            return;
        }

        if (array_key_exists('value', $clause)) {
            $or ? $query->orWhere($column, $operator, $clause['value']) : $query->where($column, $operator, $clause['value']);

            return;
        }

        $or ? $query->orWhere($column, $operator) : $query->where($column, $operator);
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
