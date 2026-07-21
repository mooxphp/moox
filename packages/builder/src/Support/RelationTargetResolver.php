<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Moox\Builder\Registry\EntityRegistry;

/**
 * Resolves Moox entity relation targets for searchable pickers and API output.
 */
final class RelationTargetResolver
{
    /**
     * Request-scoped label memo (entity => [id => label]) so repeated relation
     * targets across table rows and Livewire re-renders resolve without extra
     * queries. The resolver is bound as scoped(), so this never outlives a
     * request.
     *
     * @var array<string, array<int|string, string>>
     */
    protected array $labelMemo = [];

    public function __construct(
        protected EntityRegistry $entityRegistry,
    ) {}

    /**
     * @return class-string<Model>|null
     */
    public function modelClass(string $entity): ?string
    {
        return $this->entityRegistry->relatedModelFor($entity);
    }

    /**
     * @return class-string|null
     */
    public function resourceClass(string $entity): ?string
    {
        return $this->entityRegistry->relatedResourceFor($entity);
    }

    /**
     * @return array<int|string, string>
     */
    public function search(string $entity, string $term, int $limit = 50): array
    {
        $modelClass = $this->modelClass($entity);

        if ($modelClass === null || ! $this->entityRegistry->modelIsQueryable($modelClass)) {
            return [];
        }

        try {
            $query = $this->scopedQuery($entity, $modelClass);

            if ($term !== '') {
                $this->applySearchFilter($query, $modelClass, $term);
            }

            if ($this->modelUsesTranslations($modelClass)) {
                $query->with('translations');
            }

            $results = [];

            foreach ($query->limit($limit)->get() as $record) {
                $title = $this->titleFor($entity, $record);

                if ($title === '') {
                    continue;
                }

                $results[$record->getKey()] = $title;
            }

            return $results;
        } catch (QueryException) {
            return [];
        }
    }

    /**
     * @param  list<int|string>  $ids
     * @return array<int|string, string>
     */
    public function labelsFor(string $entity, array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, fn (mixed $id): bool => filled($id))));

        if ($ids === []) {
            return [];
        }

        $memo = $this->labelMemo[$entity] ?? [];
        $missing = array_values(array_filter($ids, fn (mixed $id): bool => ! array_key_exists($id, $memo)));

        if ($missing !== []) {
            $modelClass = $this->modelClass($entity);

            if ($modelClass === null || ! $this->entityRegistry->modelIsQueryable($modelClass)) {
                return $this->pickLabels($memo, $ids);
            }

            $model = new $modelClass;

            try {
                $query = $this->scopedQuery($entity, $modelClass)->whereIn($model->getKeyName(), $missing);

                if ($this->modelUsesTranslations($modelClass)) {
                    $query->with('translations');
                }

                foreach ($query->get() as $record) {
                    $memo[$record->getKey()] = $this->titleFor($entity, $record);
                }

                $this->labelMemo[$entity] = $memo;
            } catch (QueryException) {
                return $this->pickLabels($memo, $ids);
            }
        }

        return $this->pickLabels($memo, $ids);
    }

    /**
     * @param  array<int|string, string>  $memo
     * @param  list<int|string>  $ids
     * @return array<int|string, string>
     */
    protected function pickLabels(array $memo, array $ids): array
    {
        $labels = [];

        foreach ($ids as $id) {
            if (array_key_exists($id, $memo)) {
                $labels[$id] = $memo[$id];
            }
        }

        return $labels;
    }

    /**
     * @param  list<int|string>  $ids
     * @return list<array{id: int|string, label: string}>
     */
    public function resolve(string $entity, array $ids): array
    {
        $labels = $this->labelsFor($entity, $ids);
        $resolved = [];

        foreach ($ids as $id) {
            $key = $this->matchingKey($labels, $id);

            if ($key === null) {
                continue;
            }

            $resolved[] = [
                'id' => $key,
                'label' => $labels[$key],
            ];
        }

        return $resolved;
    }

    public function recordExists(string $entity, int|string $id): bool
    {
        $modelClass = $this->modelClass($entity);

        if ($modelClass === null || ! $this->entityRegistry->modelIsQueryable($modelClass)) {
            return false;
        }

        try {
            return $this->scopedQuery($entity, $modelClass)->whereKey($id)->exists();
        } catch (QueryException) {
            return false;
        }
    }

    /**
     * @return array{
     *     modelClass: class-string<Model>,
     *     table: string,
     *     key: string,
     *     titleColumn: string,
     *     translation?: array{
     *         table: string,
     *         foreignKey: string,
     *         localeColumn: string,
     *         titleColumn: string,
     *         softDeletes: bool,
     *     },
     * }|null
     */
    public function queryTarget(string $entity): ?array
    {
        $modelClass = $this->modelClass($entity);

        if ($modelClass === null || ! $this->entityRegistry->modelIsQueryable($modelClass)) {
            return null;
        }

        $model = new $modelClass;
        $translation = null;
        $titleColumn = 'id';

        foreach (['title', 'name', 'label', 'common_name', 'symbol', 'code'] as $candidate) {
            if ($this->modelHasColumn($modelClass, $candidate)) {
                $titleColumn = $candidate;
                break;
            }
        }

        if ($titleColumn === 'id') {
            $translation = $this->translationTargetForModel($modelClass);

            if ($translation !== null) {
                $titleColumn = $translation['titleColumn'];
            }
        }

        $target = [
            'modelClass' => $modelClass,
            'table' => $model->getTable(),
            'key' => $model->getKeyName(),
            'titleColumn' => $titleColumn,
        ];

        if ($translation !== null) {
            $target['translation'] = $translation;
        }

        return $target;
    }

    /**
     * Base query for enumerating/validating relation targets. Prefers the
     * target resource's own Eloquent query so global/tenant/soft-delete scopes
     * apply, preventing selection or validation of records that lie outside the
     * resource's intended visibility. Falls back to the plain model query when
     * no scoped resource query is available (e.g. outside a panel context).
     *
     * @param  class-string<Model>  $modelClass
     * @return Builder<Model>
     */
    protected function scopedQuery(string $entity, string $modelClass): Builder
    {
        $resource = $this->resourceClass($entity);

        if ($resource !== null && method_exists($resource, 'getEloquentQuery')) {
            try {
                return $resource::getEloquentQuery();
            } catch (\Throwable) {
                // Fall back to the unscoped model query below.
            }
        }

        return $modelClass::query();
    }

    protected function titleFor(string $entity, Model $record): string
    {
        $resource = $this->resourceClass($entity);

        if ($resource !== null
            && method_exists($resource, 'hasRecordTitle')
            && $resource::hasRecordTitle()
            && method_exists($resource, 'getRecordTitle')) {
            $title = $resource::getRecordTitle($record);

            if (filled($title)) {
                return (string) $title;
            }
        }

        foreach ($this->titleAttributeCandidates($entity) as $attribute) {
            $value = $record->getAttribute($attribute);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return (string) $record->getKey();
    }

    /**
     * @return list<string>
     */
    protected function titleAttributeCandidates(string $entity): array
    {
        $resource = $this->resourceClass($entity);

        if ($resource !== null && method_exists($resource, 'getRecordTitleAttribute')) {
            $attribute = $resource::getRecordTitleAttribute();

            if (filled($attribute)) {
                return [(string) $attribute];
            }
        }

        $modelClass = $this->modelClass($entity);
        $candidates = [
            'display_title',
            'display_name',
            'title',
            'name',
            'label',
            'common_name',
            'symbol',
            'code',
        ];

        if ($modelClass === null) {
            return $candidates;
        }

        return array_values(array_filter(
            $candidates,
            fn (string $candidate): bool => $this->modelHasColumn($modelClass, $candidate)
                || $this->modelHasAccessor($modelClass, $candidate)
                || $this->modelHasTranslatableAttribute($modelClass, $candidate),
        ));
    }

    protected function titleAttributeFor(string $entity): string
    {
        $modelClass = $this->modelClass($entity);

        foreach (['title', 'name', 'label'] as $candidate) {
            if ($modelClass !== null && $this->modelHasColumn($modelClass, $candidate)) {
                return $candidate;
            }
        }

        if ($modelClass !== null && $this->modelHasTranslatableAttribute($modelClass, 'title')) {
            return 'title';
        }

        if ($modelClass !== null && $this->modelHasTranslatableAttribute($modelClass, 'name')) {
            return 'name';
        }

        return 'id';
    }

    /**
     * @param  Builder<Model>  $query
     * @param  class-string<Model>  $modelClass
     */
    protected function applySearchFilter(Builder $query, string $modelClass, string $term): void
    {
        $columns = [];

        foreach (['title', 'name', 'label', 'common_name', 'symbol', 'code'] as $column) {
            if ($this->modelHasColumn($modelClass, $column)) {
                $columns[] = $column;
            }
        }

        if ($columns !== []) {
            $query->where(function (Builder $builder) use ($columns, $term): void {
                foreach ($columns as $column) {
                    $builder->orWhere($column, 'like', "%{$term}%");
                }
            });

            return;
        }

        foreach (['title', 'name', 'label'] as $attribute) {
            if (! $this->modelHasTranslatableAttribute($modelClass, $attribute)) {
                continue;
            }

            $query->whereHas('translations', function (Builder $translationQuery) use ($attribute, $term): void {
                $translationQuery->where($attribute, 'like', "%{$term}%");
            });

            return;
        }
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array{
     *     table: string,
     *     foreignKey: string,
     *     localeColumn: string,
     *     titleColumn: string,
     *     softDeletes: bool,
     * }|null
     */
    protected function translationTargetForModel(string $modelClass): ?array
    {
        if (! $this->modelUsesTranslations($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        $instance = new $modelClass;

        if (! method_exists($instance, 'translations')) {
            return null;
        }

        $titleColumn = null;

        foreach (['title', 'name', 'label'] as $candidate) {
            if ($this->modelHasTranslatableAttribute($modelClass, $candidate)) {
                $titleColumn = $candidate;
                break;
            }
        }

        if ($titleColumn === null) {
            return null;
        }

        $relation = $instance->translations();
        $translationModel = $relation->getRelated();

        if (! $translationModel instanceof Model) {
            return null;
        }

        $translationModelClass = $translationModel::class;
        $localeColumn = method_exists($instance, 'getLocaleKey')
            ? $instance->getLocaleKey()
            : 'locale';
        $foreignKey = method_exists($instance, 'getTranslationRelationKey')
            ? $instance->getTranslationRelationKey()
            : $relation->getForeignKeyName();

        return [
            'table' => $translationModel->getTable(),
            'foreignKey' => $foreignKey,
            'localeColumn' => $localeColumn,
            'titleColumn' => $titleColumn,
            'softDeletes' => in_array(
                SoftDeletes::class,
                class_uses_recursive($translationModelClass),
                true,
            ),
        ];
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function modelUsesTranslations(string $modelClass): bool
    {
        if (! is_subclass_of($modelClass, Model::class)) {
            return false;
        }

        $instance = new $modelClass;

        return method_exists($instance, 'translations')
            && (
                $this->modelHasTranslatableAttribute($modelClass, 'title')
                || $this->modelHasTranslatableAttribute($modelClass, 'name')
                || $this->modelHasTranslatableAttribute($modelClass, 'label')
            );
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function modelHasTranslatableAttribute(string $modelClass, string $attribute): bool
    {
        if (! is_subclass_of($modelClass, Model::class)) {
            return false;
        }

        $instance = new $modelClass;

        if (! property_exists($instance, 'translatedAttributes') || ! is_array($instance->translatedAttributes)) {
            return false;
        }

        return in_array($attribute, $instance->translatedAttributes, true);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function modelHasAccessor(string $modelClass, string $attribute): bool
    {
        if (! is_subclass_of($modelClass, Model::class)) {
            return false;
        }

        $instance = new $modelClass;

        if (in_array($attribute, $instance->getAppends(), true)) {
            return true;
        }

        $studly = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $attribute)));
        $method = 'get'.$studly.'Attribute';

        return method_exists($instance, $method);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function modelHasColumn(string $modelClass, string $column): bool
    {
        try {
            $table = (new $modelClass)->getTable();

            return filled($table) && $this->entityRegistry->databaseTableHasColumn($table, $column);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  array<int|string, string>  $labels
     */
    protected function matchingKey(array $labels, mixed $id): int|string|null
    {
        if (array_key_exists($id, $labels)) {
            return $id;
        }

        if (is_numeric($id)) {
            $intId = (int) $id;

            if (array_key_exists($intId, $labels)) {
                return $intId;
            }
        }

        $stringId = (string) $id;

        if (array_key_exists($stringId, $labels)) {
            return $stringId;
        }

        return null;
    }
}
