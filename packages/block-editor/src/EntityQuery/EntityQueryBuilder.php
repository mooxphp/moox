<?php

declare(strict_types=1);

namespace Moox\BlockEditor\EntityQuery;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Moox\BlockEditor\Support\BlockEditorLocale;

final class EntityQueryBuilder
{
    private Builder $query;

    private string $locale = '';

    private string $parentTable = '';

    private string $translationTable = '';

    private string $translationForeignKey = '';

    private string $translationAlias = 'nt';

    private bool $joinedTranslations = false;

    /** @var array<int|string, mixed> */
    private array $eagerLoads = [];

    public function for(string $modelClass, EntityQueryDefinition $definition): self
    {
        if (! is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException("Model class [{$modelClass}] must extend ".Model::class.'.');
        }

        if (! is_subclass_of($modelClass, TranslatableContract::class)) {
            throw new InvalidArgumentException("Model class [{$modelClass}] must implement ".TranslatableContract::class.'.');
        }

        $this->locale = $definition->locale;

        /** @var Model&TranslatableContract $model */
        $model = new $modelClass;

        $this->parentTable = $model->getTable();
        $translationRelation = $model->translations();
        $this->translationTable = $translationRelation->getRelated()->getTable();
        $this->translationForeignKey = $translationRelation->getForeignKeyName();
        $this->query = $modelClass::query();
        $this->joinedTranslations = false;

        return $this;
    }

    public function withDraftDefaults(string $locale): self
    {
        $resolvedLocale = BlockEditorLocale::resolveTranslationLocale($locale);
        $localeCandidates = BlockEditorLocale::localeCandidates($resolvedLocale);

        if ($localeCandidates === []) {
            $localeCandidates = [$resolvedLocale];
        }

        $this->locale = $resolvedLocale;

        $this->query->where($this->parentTable.'.is_active', true);

        if (! $this->joinedTranslations) {
            $alias = $this->translationAlias;

            $this->query->join(
                "{$this->translationTable} as {$alias}",
                function ($join) use ($localeCandidates, $alias): void {
                    $join
                        ->on("{$this->parentTable}.id", '=', "{$alias}.{$this->translationForeignKey}")
                        ->whereIn("{$alias}.locale", $localeCandidates)
                        ->where("{$alias}.translation_status", 'published')
                        ->whereNotNull("{$alias}.published_at")
                        ->whereNull("{$alias}.deleted_at");
                }
            );

            $this->query
                ->select("{$this->parentTable}.*")
                ->distinct();
            $this->joinedTranslations = true;
        }

        return $this;
    }

    /**
     * @param  array<string, array<string, mixed>>  $filterSchema
     * @param  array<string, mixed>  $filters
     */
    public function applyFilters(array $filterSchema, array $filters): self
    {
        foreach ($filterSchema as $filterKey => $schema) {
            $value = $filters[$filterKey] ?? null;

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $apply = $schema['apply'] ?? null;

            if (! is_string($apply) || $apply === '') {
                continue;
            }

            $this->applyFilterConvention($apply, $value);
        }

        return $this;
    }

    /**
     * @param  array<string, string>  $sortableColumns
     */
    public function applySort(array $sortableColumns, EntityQueryDefinition $definition): self
    {
        $column = $sortableColumns[$definition->orderBy]
            ?? $sortableColumns['published_at']
            ?? null;

        if (! is_string($column) || $column === '') {
            return $this;
        }

        if ($this->joinedTranslations) {
            $this->query->addSelect($column);
        }

        $direction = $definition->orderDirection === 'asc' ? 'asc' : 'desc';
        $this->query->orderBy($column, $direction);

        return $this;
    }

    public function limit(int $limit): self
    {
        $maxLimit = (int) config('moox-editor.dynamic_feed.max_limit', 50);
        $this->query->limit(max(1, min($maxLimit, $limit)));

        return $this;
    }

    /**
     * @param  array<int|string, mixed>  $relations
     */
    public function withEagerLoads(array $relations): self
    {
        $this->eagerLoads = $relations;

        return $this;
    }

    /**
     * @return Collection<int, Model>
     */
    public function get(): Collection
    {
        $localeCandidates = BlockEditorLocale::localeCandidates($this->locale) ?: [$this->locale];

        $relations = $this->eagerLoads;

        if (! array_key_exists('translations', $relations)) {
            $relations['translations'] = fn ($query) => $query->whereIn('locale', $localeCandidates);
        }

        $this->query->with($relations);

        return $this->query->get();
    }

    private function applyFilterConvention(string $apply, mixed $value): void
    {
        if (str_starts_with($apply, 'taxonomy:')) {
            $relation = Str::after($apply, 'taxonomy:');
            $this->query->whereHas($relation, function (Builder $query) use ($value): void {
                $query->whereKey($value);
            });

            return;
        }

        if (str_starts_with($apply, 'column:')) {
            $column = Str::after($apply, 'column:');
            $this->query->where("{$this->parentTable}.{$column}", $value);
        }
    }
}
