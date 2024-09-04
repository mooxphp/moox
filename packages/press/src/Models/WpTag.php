<?php

namespace Moox\Press\Models;

use Illuminate\Database\Eloquent\Builder;

class WpTag extends WpTerm
{
    protected $taxonomy = 'tag';

    public static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('tag', function (Builder $builder) {
            $builder->whereHas('termTaxonomy', function ($query) {
                $query->where('taxonomy', 'post_tag');
            });
        });
    }
}
