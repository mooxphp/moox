<?php

namespace Moox\Press\Models;

use Override;
use Illuminate\Database\Eloquent\Builder;

class WpPage extends WpBasePost
{
    #[Override]protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('page', function (Builder $builder): void {
            $builder->where('post_type', 'page');
        });
    }
}
