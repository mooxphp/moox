<?php

namespace Moox\Press\Models;

use Illuminate\Database\Eloquent\Builder;
use Override;

class WpTag extends WpTerm
{
    protected $taxonomy = 'tag';

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('tag', function (Builder $builder): void {
            $builder->whereHas('termTaxonomy', function ($query): void {
                $query->where('taxonomy', 'post_tag');
            });
        });
    }
}
