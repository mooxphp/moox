<?php

namespace Moox\PressWiki\Models;

use Moox\Press\Models\WpTerm;
use Illuminate\Database\Eloquent\Builder;

class WpThema extends WpTerm
{
    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('thema', function (Builder $builder) {
            $builder->where('post_type', 'thema');
        });
    }
}
