<?php

declare(strict_types=1);

namespace Moox\Tree\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Moox\Tree\Config\TreeIndexConfiguration;

final class TreeIndexQueryBuilder
{
    public function __construct(private readonly TreeIndexConfiguration $configuration) {}

    public function applyQuery(Builder $query): Builder
    {
        return $this->configuration->applyQueryClosure($query);
    }

    public function newQuery(): Builder
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $this->configuration->modelClass();

        return $this->applyQuery($modelClass::query());
    }

    public function siblingsQuery(int|string|null $parentId): Builder
    {
        $parentColumn = $this->configuration->getParentColumn();
        $parentId = $parentId === null ? null : (int) $parentId;

        return $this->newQuery()
            ->when(
                $parentId === null,
                fn (Builder $query): Builder => $query->whereNull($parentColumn),
                fn (Builder $query): Builder => $query->where($parentColumn, $parentId),
            );
    }

    public function siblingsExcept(int|string|null $parentId, int|string|null $excludeId): Builder
    {
        $query = $this->applyTreeOrdering($this->siblingsQuery($parentId));

        if ($excludeId !== null && $excludeId !== '') {
            $query->whereKeyNot((int) $excludeId);
        }

        return $query;
    }

    public function nextSortOrder(int|string|null $parentId): int
    {
        $sortColumn = $this->configuration->getSortColumn();
        $maxSort = $this->siblingsQuery($parentId)->max($sortColumn);

        return ((int) $maxSort) + 10;
    }

    public function applyTreeOrdering(Builder $query): Builder
    {
        $query->orderBy($this->configuration->getSortColumn());

        if ($this->configuration->isLabelColumnQueryable()) {
            $query->orderBy($this->configuration->getLabelColumn());
        }

        return $query;
    }

    public function applySearch(Builder $query, string $search): Builder
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        $callback = $this->configuration->getApplySearchUsing();

        if ($callback !== null) {
            return $callback($query, $search, $this->configuration);
        }

        if (! $this->configuration->isLabelColumnQueryable()) {
            return $query;
        }

        return $query->where($this->configuration->getLabelColumn(), 'like', '%'.$search.'%');
    }

    public function applyLanguage(Builder $query, string $lang): Builder
    {
        $lang = trim($lang);

        if ($lang === '') {
            return $query;
        }

        $callback = $this->configuration->getApplyLanguageUsing();

        if ($callback === null) {
            return $query;
        }

        return $callback($query, $lang, $this->configuration);
    }
}
