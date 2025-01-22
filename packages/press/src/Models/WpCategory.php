<?php

namespace Moox\Press\Models;

use Override;
use Illuminate\Database\Eloquent\Builder;

class WpCategory extends WpTerm
{
    protected $taxonomy = 'category';

    #[Override]protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('category', function (Builder $builder): void {
            $builder->whereHas('termTaxonomy', function ($query): void {
                $query->where('taxonomy', 'category');
            });
        });
    }
}
