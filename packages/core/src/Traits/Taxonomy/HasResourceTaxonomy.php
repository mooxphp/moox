<?php

namespace Moox\Core\Traits\Taxonomy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Relations\HasInlineRelationFields;

trait HasResourceTaxonomy
{
    use HasInlineRelationFields;

    public static function getTaxonomyFields(): array
    {
        return static::getInlineRelationFields();
    }

    public static function getTaxonomyFilters(): array
    {
        return static::getInlineRelationFilters();
    }

    protected static function getTaxonomyColumns(): array
    {
        return static::getInlineRelationColumns();
    }

    protected static function handleTaxonomies(Model $record, array $data): void
    {
        static::handleInlineRelations($record, $data);
    }

    protected static function addTaxonomyRelationsToQuery(Builder $query): Builder
    {
        return static::addInlineRelationsToQuery($query);
    }
}
