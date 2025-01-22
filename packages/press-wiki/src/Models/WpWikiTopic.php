<?php

namespace Moox\PressWiki\Models;

use Override;
use Illuminate\Database\Eloquent\Builder;
use Moox\Press\Models\WpTerm;

class WpWikiTopic extends WpTerm
{
    #[Override]protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('topic', function (Builder $builder): void {
            $builder->whereHas('termTaxonomy', function ($query): void {
                $query->where('taxonomy', 'thema');
            });
        });
    }
}
