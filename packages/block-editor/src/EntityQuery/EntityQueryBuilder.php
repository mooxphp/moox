<?php

declare(strict_types=1);

namespace Moox\BlockEditor\EntityQuery;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class EntityQueryBuilder
{
    private Builder $query;

    private string $locale = '';

    private string $parentTable = '';

    private string $translationTable = '';

    private string $translationForeignKey = '';

    private string $translationAlias = 'nt';

    private bool $joinedTranslations = false;

    public function for(string $modelClass, EntityQueryDefinition $definition): self
    {
        $this->locale = $definition->locale;

        /** @var \Illuminate\Database\Eloquent\Model $model */
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
        $resolvedLocale = \Moox\BlockEditor\Support\BlockEditorLocale::resolveTranslationLocale($locale);
        $localeCandidates = \Moox\BlockEditor\Support\BlockEditorLocale::localeCandidates($resolvedLocale);

        if ($localeCandidates === []) {
            $localeCandidates = [$resolvedLocale];
        }

        $this->locale = $resolvedLocale;

        $this->query
            ->where($this->parentTable.'.is_active', true)
            ->whereHas('translations', function ($query) use ($localeCandidates): void {
                $query
                    ->whereIn('locale', $localeCandidates)
                    ->where('translation_status', 'published')
                    ->whereNotNull('published_at')
                    ->whereNull('deleted_at');
            });

        if (! $this->joinedTranslations) {
            $alias = $this->translationAlias;

            $this->query->join(
                "{$this->translationTable} as {$alias}",
                function ($join) use ($localeCandidates, $alias): void {
                    $join
                        ->on("{$this->parentTable}.id", '=', "{$alias}.{$this->translationForeignKey}")
                        ->whereIn("{$alias}.locale", $localeCandidates)
                        ->where("{$alias}.translation_status", 'published')
                        ->whereNull("{$alias}.deleted_at");
                }
            );

            $this->query->select("{$this->parentTable}.*");
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
            if (! is_array($schema)) {
                continue;
            }

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
     * @return Collection<int, \Illuminate\Database\Eloquent\Model>
     */
    public function get(): Collection
    {
        $this->query->with([
            'translations' => fn ($query) => $query->whereIn('locale', \Moox\BlockEditor\Support\BlockEditorLocale::localeCandidates($this->locale) ?: [$this->locale]),
        ]);

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
