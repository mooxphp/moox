<?php

namespace Moox\Press\Models;

use Illuminate\Database\Eloquent\Builder;
use Override;

class WpPost extends WpBasePost
{
    #[Override]
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('post', function (Builder $builder): void {
            $builder
                ->where('post_type', 'post')
                ->whereIn('post_status', ['publish', 'draft', 'pending', 'trash', 'future', 'private'])
                ->orderBy('post_date', 'desc');
        });
    }
}
