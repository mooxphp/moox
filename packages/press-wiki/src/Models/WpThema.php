<?php

namespace Moox\PressWiki\Models;

use Illuminate\Database\Eloquent\Builder;
use Moox\Press\Models\WpTerm;

class WpThema extends WpTerm
{
    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('thema', function (Builder $builder) {
            $builder->whereHas('termTaxonomy', function ($query) {
                $query->where('taxonomy', 'thema');
            });
        });
    }
}
