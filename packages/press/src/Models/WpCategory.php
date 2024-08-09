<?php

namespace Moox\Press\Models;

use Illuminate\Database\Eloquent\Builder;

class WpCategory extends WpTerm
{
    protected $taxonomy = 'category';

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('category', function (Builder $builder) {
            $builder->whereHas('termTaxonomy', function ($query) {
                $query->where('taxonomy', 'category');
            });
        });
    }
}
