<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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

        $titleAttribute = $this->titleAttributeFor($entity);

        if (! $this->modelHasColumn($modelClass, $titleAttribute)) {
            return [];
        }

        try {
            $query = $this->scopedQuery($entity, $modelClass);

            if ($term !== '') {
                $query->where($titleAttribute, 'like', "%{$term}%");
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
                foreach ($modelClass::query()->whereIn($model->getKeyName(), $missing)->get() as $record) {
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
     * @return array{modelClass: class-string<Model>, table: string, key: string, titleColumn: string}|null
     */
    public function queryTarget(string $entity): ?array
    {
        $modelClass = $this->modelClass($entity);

        if ($modelClass === null || ! $this->entityRegistry->modelIsQueryable($modelClass)) {
            return null;
        }

        $model = new $modelClass;
        $titleColumn = $this->titleAttributeFor($entity);

        if ($titleColumn !== 'id' && ! $this->modelHasColumn($modelClass, $titleColumn)) {
            $titleColumn = 'id';
        }

        return [
            'modelClass' => $modelClass,
            'table' => $model->getTable(),
            'key' => $model->getKeyName(),
            'titleColumn' => $titleColumn,
        ];
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
        $candidates = ['display_name', 'title', 'name', 'label'];

        if ($modelClass === null) {
            return $candidates;
        }

        return array_values(array_filter(
            $candidates,
            fn (string $candidate): bool => $this->modelHasColumn($modelClass, $candidate)
                || $this->modelHasAccessor($modelClass, $candidate),
        ));
    }

    protected function titleAttributeFor(string $entity): string
    {
        $candidates = $this->titleAttributeCandidates($entity);

        foreach ($candidates as $candidate) {
            if ($candidate === 'display_name') {
                continue;
            }

            $modelClass = $this->modelClass($entity);

            if ($modelClass !== null && $this->modelHasColumn($modelClass, $candidate)) {
                return $candidate;
            }
        }

        return 'id';
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
