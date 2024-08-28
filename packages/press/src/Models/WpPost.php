<?php

namespace Moox\Press\Models;

use Illuminate\Database\Eloquent\Builder;

class WpPost extends WpBasePost
{
    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('post', function (Builder $builder) {
            $builder
                ->where('post_type', 'post')
                ->whereIn('post_status', ['publish', 'draft', 'pending', 'trash', 'future', 'private'])
                ->orderBy('post_date', 'desc');
        });
    }
}
