<?php

namespace Moox\Press\Models;

use Illuminate\Database\Eloquent\Builder;

class WpPage extends WpBasePost
{
    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('page', function (Builder $builder) {
            $builder->where('post_type', 'page');
        });
    }
}
