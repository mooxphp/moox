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

    private const TRANSLATION_ALIAS = 'relation_target_translation';

    public function __construct(
        protected RelationTargetResolver $resolver,
    ) {
    }

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
        $translation = $target['translation'] ?? null;

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
            $translation,
            $like,
        ): void {
            $subquery->from($valuesTable)
                ->selectRaw('1')
                ->whereColumn("{$valuesTable}.record_id", $recordKey)
                ->where("{$valuesTable}.entity", $entity)
                ->where("{$valuesTable}.field_name", $field->name)
                ->where("{$valuesTable}.locale", $locale);

            if ($target['multiple']) {
                $bindings = $translation !== null ? [$locale, $like] : [$like];
                $subquery->whereRaw(
                    $this->multipleRelationSearchSql(
                        $valuesTable,
                        $relatedTable,
                        $relatedKey,
                        $titleColumn,
                        $alias,
                        $translation,
                    ),
                    $bindings,
                );

                return;
            }

            $subquery->join("{$relatedTable} as {$alias}", function ($join) use ($valuesTable, $alias, $relatedKey): void {
                $join->whereRaw($this->singleRelationJoinSql($valuesTable, $alias, $relatedKey));
            });

            if ($translation !== null) {
                $this->applyTranslationJoin($subquery, $alias, $relatedKey, $translation, self::TRANSLATION_ALIAS, $locale);
                $subquery->where(self::TRANSLATION_ALIAS.'.'.$titleColumn, 'like', $like);

                return;
            }

            $subquery->where("{$alias}.{$titleColumn}", 'like', $like);
        });
    }

    /**
     * @param  array{
     *     relatedEntity: string,
     *     modelClass: class-string<Model>,
     *     table: string,
     *     key: string,
     *     titleColumn: string,
     *     multiple: bool,
     *     translation?: array{
     *         table: string,
     *         foreignKey: string,
     *         localeColumn: string,
     *         titleColumn: string,
     *         softDeletes: bool,
     *     },
     * }  $target
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
        $translation = $target['translation'] ?? null;

        $query = DB::table($valuesTable)
            ->whereColumn("{$valuesTable}.record_id", $recordKey)
            ->where("{$valuesTable}.entity", $entity)
            ->where("{$valuesTable}.field_name", $field->name)
            ->where("{$valuesTable}.locale", $locale);

        if ($target['multiple']) {
            return $this->multipleTitleSubquery(
                $query,
                $valuesTable,
                $relatedTable,
                $relatedKey,
                $titleColumn,
                $alias,
                $translation,
                $locale,
            );
        }

        $query
            ->join("{$relatedTable} as {$alias}", function ($join) use ($valuesTable, $alias, $relatedKey): void {
                $join->whereRaw($this->singleRelationJoinSql($valuesTable, $alias, $relatedKey));
            });

        if ($translation !== null) {
            $this->applyTranslationJoin($query, $alias, $relatedKey, $translation, self::TRANSLATION_ALIAS, $locale);

            return $query
                ->select(self::TRANSLATION_ALIAS.'.'.$titleColumn)
                ->limit(1);
        }

        return $query
            ->select("{$alias}.{$titleColumn}")
            ->limit(1);
    }

    /**
     * @param  array{
     *     table: string,
     *     foreignKey: string,
     *     localeColumn: string,
     *     titleColumn: string,
     *     softDeletes: bool,
     * }  $translation
     */
    protected function applyTranslationJoin(
        \Illuminate\Database\Query\Builder $query,
        string $relatedAlias,
        string $relatedKey,
        array $translation,
        string $translationAlias,
        string $locale,
    ): void {
        $query->join("{$translation['table']} as {$translationAlias}", function ($join) use (
            $relatedAlias,
            $relatedKey,
            $translation,
            $translationAlias,
            $locale,
        ): void {
            $join->on(
                "{$translationAlias}.{$translation['foreignKey']}",
                '=',
                "{$relatedAlias}.{$relatedKey}",
            )->where("{$translationAlias}.{$translation['localeColumn']}", '=', $locale);

            if ($translation['softDeletes']) {
                $join->whereNull("{$translationAlias}.deleted_at");
            }
        });
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
     * @param  array{
     *     table: string,
     *     foreignKey: string,
     *     localeColumn: string,
     *     titleColumn: string,
     *     softDeletes: bool,
     * }|null  $translation
     * @return \Illuminate\Database\Query\Builder
     */
    protected function multipleTitleSubquery(
        \Illuminate\Database\Query\Builder $query,
        string $valuesTable,
        string $relatedTable,
        string $relatedKey,
        string $titleColumn,
        string $alias,
        ?array $translation,
        string $locale,
    ) {
        $titleExpression = $translation !== null
            ? 'MIN('.self::TRANSLATION_ALIAS.'.'.$titleColumn.')'
            : "MIN({$alias}.{$titleColumn})";

        $baseQuery = match (DB::connection()->getDriverName()) {
            'mysql' => $query
                ->join(DB::raw(
                    "JSON_TABLE({$valuesTable}.value_json, '\$[*]' COLUMNS (value BIGINT PATH '\$')) AS relation_value"
                ), DB::raw('1'), '=', DB::raw('1'))
                ->join("{$relatedTable} as {$alias}", "{$alias}.{$relatedKey}", '=', 'relation_value.value'),
            default => $query
                ->join(DB::raw("json_each({$valuesTable}.value_json) as relation_value"), DB::raw('1'), '=', DB::raw('1'))
                ->join("{$relatedTable} as {$alias}", "{$alias}.{$relatedKey}", '=', 'relation_value.value'),
        };

        if ($translation !== null) {
            $this->applyTranslationJoin($baseQuery, $alias, $relatedKey, $translation, self::TRANSLATION_ALIAS, $locale);
        }

        return $baseQuery
            ->selectRaw($titleExpression)
            ->limit(1);
    }

    /**
     * @param  array{
     *     table: string,
     *     foreignKey: string,
     *     localeColumn: string,
     *     titleColumn: string,
     *     softDeletes: bool,
     * }|null  $translation
     */
    protected function multipleRelationSearchSql(
        string $valuesTable,
        string $relatedTable,
        string $relatedKey,
        string $titleColumn,
        string $alias,
        ?array $translation,
    ): string {
        $translationAlias = self::TRANSLATION_ALIAS;
        $titleRef = $translation !== null
            ? "{$translationAlias}.{$titleColumn}"
            : "{$alias}.{$titleColumn}";
        $translationJoin = '';

        if ($translation !== null) {
            $softDeleteClause = $translation['softDeletes']
                ? " AND {$translationAlias}.deleted_at IS NULL"
                : '';
            $translationJoin = "INNER JOIN {$translation['table']} AS {$translationAlias}
                    ON {$translationAlias}.{$translation['foreignKey']} = {$alias}.{$relatedKey}
                    AND {$translationAlias}.{$translation['localeColumn']} = ?{$softDeleteClause}
                ";
        }

        return match (DB::connection()->getDriverName()) {
            'mysql' => "EXISTS (
                SELECT 1
                FROM JSON_TABLE({$valuesTable}.value_json, '\$[*]' COLUMNS (value BIGINT PATH '\$')) AS relation_value
                INNER JOIN {$relatedTable} AS {$alias}
                    ON {$alias}.{$relatedKey} = relation_value.value
                {$translationJoin}
                WHERE {$titleRef} LIKE ?
            )",
            default => "EXISTS (
                SELECT 1
                FROM json_each({$valuesTable}.value_json) AS relation_value
                INNER JOIN {$relatedTable} AS {$alias}
                    ON {$alias}.{$relatedKey} = relation_value.value
                {$translationJoin}
                WHERE {$titleRef} LIKE ?
            )",
        };
    }

    /**
     * @return array{
     *     relatedEntity: string,
     *     modelClass: class-string<Model>,
     *     table: string,
     *     key: string,
     *     titleColumn: string,
     *     multiple: bool,
     *     translation?: array{
     *         table: string,
     *         foreignKey: string,
     *         localeColumn: string,
     *         titleColumn: string,
     *         softDeletes: bool,
     *     },
     * }|null
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
