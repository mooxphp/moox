<?php

namespace Moox\PressWiki\Models;

use Illuminate\Database\Eloquent\Builder;
use Moox\Press\Models\WpPost;

class WpWiki extends WpPost
{
    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('wiki', function (Builder $builder) {
            $builder->where('post_type', 'wiki');
        });
    }
}
