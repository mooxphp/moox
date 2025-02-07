<?php

namespace Moox\Press\Models;

use Illuminate\Database\Eloquent\Builder;
use Override;

class WpCategory extends WpTerm
{
    protected $taxonomy = 'category';

    #[Override]
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('category', function (Builder $builder): void {
            $builder->whereHas('termTaxonomy', function ($query): void {
                $query->where('taxonomy', 'category');
            });
        });
    }
}
