<?php

namespace Moox\Press\Models;

use Override;
use Illuminate\Database\Eloquent\Builder;

class WpTag extends WpTerm
{
    protected $taxonomy = 'tag';

    #[Override]
    public static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('tag', function (Builder $builder): void {
            $builder->whereHas('termTaxonomy', function ($query): void {
                $query->where('taxonomy', 'post_tag');
            });
        });
    }
}
