<?php

namespace Moox\PressWiki\Models;

use Illuminate\Database\Eloquent\Builder;
use Moox\Press\Models\WpTerm;
use Override;

class WpTopic extends WpTerm
{
    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('thema', function (Builder $builder): void {
            $builder->whereHas('termTaxonomy', function ($query): void {
                $query->where('taxonomy', 'thema');
            });
        });
    }
}
