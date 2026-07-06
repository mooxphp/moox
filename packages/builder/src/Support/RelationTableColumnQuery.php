<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Moox\Builder\Data\FieldDefinition;

/**
 * Sort/search queries for relation custom-field table columns. Resolves linked
 * record titles through the related model table instead of raw JSON ids.
 */
final class RelationTableColumnQuery
{
    private const RELATED_ALIAS = 'relation_target';

    public function __construct(
        protected RelationTargetResolver $resolver,
    ) {}

    public function canQuery(FieldDefinition $field): bool
    {
        return $this->target($field) !== null;
    }

    public function applySort(
        Builder $query,
        FieldDefinition $field,
        string $entity,
        string $locale,
        string $valuesTable,
        string $direction,
    ): Builder {
        $target = $this->target($field);

        if ($target === null) {
            return $query;
        }

        $recordKey = $query->getModel()->getQualifiedKeyName();
        $subquery = $this->titleSubquery(
            $field,
            $entity,
            $locale,
            $valuesTable,
            $recordKey,
            $target,
        );

        if ($subquery === null) {
            return $query;
        }

        return $query->orderBy($subquery, $direction);
    }

    public function applySearch(
        Builder $query,
        FieldDefinition $field,
        string $entity,
        string $locale,
        string $valuesTable,
        string $search,
    ): Builder {
        $target = $this->target($field);

        if ($target === null) {
            return $query;
        }

        $recordKey = $query->getModel()->getQualifiedKeyName();
        $like = '%'.addcslashes($search, '%_\\').'%';
        $relatedTable = $target['table'];
        $relatedKey = $target['key'];
        $titleColumn = $target['titleColumn'];
        $alias = self::RELATED_ALIAS;

        return $query->whereExists(function ($subquery) use (
            $field,
            $entity,
            $locale,
            $valuesTable,
            $recordKey,
            $target,
            $relatedTable,
            $relatedKey,
            $titleColumn,
            $alias,
            $like,
        ): void {
            $subquery->from($valuesTable)
                ->selectRaw('1')
                ->whereColumn("{$valuesTable}.record_id", $recordKey)
                ->where("{$valuesTable}.entity", $entity)
                ->where("{$valuesTable}.field_name", $field->name)
                ->where("{$valuesTable}.locale", $locale);

            if ($target['multiple']) {
                $subquery->whereRaw(
                    $this->multipleRelationSearchSql($valuesTable, $relatedTable, $relatedKey, $titleColumn, $alias),
                    [$like],
                );

                return;
            }

            $subquery
                ->join("{$relatedTable} as {$alias}", function ($join) use ($valuesTable, $alias, $relatedKey): void {
                    $join->whereRaw($this->singleRelationJoinSql($valuesTable, $alias, $relatedKey));
                })
                ->where("{$alias}.{$titleColumn}", 'like', $like);
        });
    }

    /**
     * @param  array{relatedEntity: string, modelClass: class-string<Model>, table: string, key: string, titleColumn: string, multiple: bool}  $target
     */
    protected function titleSubquery(
        FieldDefinition $field,
        string $entity,
        string $locale,
        string $valuesTable,
        string $recordKey,
        array $target,
    ): ?\Illuminate\Database\Query\Builder {
        $relatedTable = $target['table'];
        $relatedKey = $target['key'];
        $titleColumn = $target['titleColumn'];
        $alias = self::RELATED_ALIAS;

        $query = DB::table($valuesTable)
            ->whereColumn("{$valuesTable}.record_id", $recordKey)
            ->where("{$valuesTable}.entity", $entity)
            ->where("{$valuesTable}.field_name", $field->name)
            ->where("{$valuesTable}.locale", $locale);

        if ($target['multiple']) {
            return $this->multipleTitleSubquery($query, $valuesTable, $relatedTable, $relatedKey, $titleColumn, $alias);
        }

        return $query
            ->select("{$alias}.{$titleColumn}")
            ->join("{$relatedTable} as {$alias}", function ($join) use ($valuesTable, $alias, $relatedKey): void {
                $join->whereRaw($this->singleRelationJoinSql($valuesTable, $alias, $relatedKey));
            })
            ->limit(1);
    }

    protected function singleRelationJoinSql(string $valuesTable, string $relatedAlias, string $relatedKey): string
    {
        $idExpression = $this->jsonScalarIdExpression("{$valuesTable}.value_json");

        return "{$relatedAlias}.{$relatedKey} = {$idExpression}";
    }

    protected function jsonScalarIdExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'mysql' => "CAST(JSON_UNQUOTE(JSON_EXTRACT({$column}, '\$')) AS UNSIGNED)",
            'pgsql' => "CAST({$column} #>> '{}' AS BIGINT)",
            default => "CAST(json_extract({$column}, '\$') AS INTEGER)",
        };
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    protected function multipleTitleSubquery(
        \Illuminate\Database\Query\Builder $query,
        string $valuesTable,
        string $relatedTable,
        string $relatedKey,
        string $titleColumn,
        string $alias,
    ) {
        return match (DB::connection()->getDriverName()) {
            'mysql' => $query
                ->selectRaw("MIN({$alias}.{$titleColumn})")
                ->join(DB::raw(
                    "JSON_TABLE({$valuesTable}.value_json, '\$[*]' COLUMNS (value BIGINT PATH '\$')) AS relation_value"
                ), DB::raw('1'), '=', DB::raw('1'))
                ->join("{$relatedTable} as {$alias}", "{$alias}.{$relatedKey}", '=', 'relation_value.value')
                ->limit(1),
            default => $query
                ->selectRaw("MIN({$alias}.{$titleColumn})")
                ->join(DB::raw("json_each({$valuesTable}.value_json) as relation_value"), DB::raw('1'), '=', DB::raw('1'))
                ->join("{$relatedTable} as {$alias}", "{$alias}.{$relatedKey}", '=', 'relation_value.value')
                ->limit(1),
        };
    }

    protected function multipleRelationSearchSql(
        string $valuesTable,
        string $relatedTable,
        string $relatedKey,
        string $titleColumn,
        string $alias,
    ): string {
        return match (DB::connection()->getDriverName()) {
            'mysql' => "EXISTS (
                SELECT 1
                FROM JSON_TABLE({$valuesTable}.value_json, '\$[*]' COLUMNS (value BIGINT PATH '\$')) AS relation_value
                INNER JOIN {$relatedTable} AS {$alias}
                    ON {$alias}.{$relatedKey} = relation_value.value
                WHERE {$alias}.{$titleColumn} LIKE ?
            )",
            default => "EXISTS (
                SELECT 1
                FROM json_each({$valuesTable}.value_json) AS relation_value
                INNER JOIN {$relatedTable} AS {$alias}
                    ON {$alias}.{$relatedKey} = relation_value.value
                WHERE {$alias}.{$titleColumn} LIKE ?
            )",
        };
    }

    /**
     * @return array{relatedEntity: string, modelClass: class-string<Model>, table: string, key: string, titleColumn: string, multiple: bool}|null
     */
    protected function target(FieldDefinition $field): ?array
    {
        $relatedEntity = RelationValueRules::relatedEntity($field);

        if ($relatedEntity === null) {
            return null;
        }

        $queryTarget = $this->resolver->queryTarget($relatedEntity);

        if ($queryTarget === null) {
            return null;
        }

        return [
            'relatedEntity' => $relatedEntity,
            ...$queryTarget,
            'multiple' => RelationValueRules::isMultiple($field),
        ];
    }
}
