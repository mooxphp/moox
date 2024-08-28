<?php

namespace Moox\PressWiki\Models;

use Illuminate\Database\Eloquent\Builder;
use Moox\Press\Models\WpTerm;

class WpWikiLocationTopic extends WpTerm
{
    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('location', function (Builder $builder) {
            $builder->whereHas('termTaxonomy', function ($query) {
                $query->where('taxonomy', 'standorte');
            });
        });
    }
}
