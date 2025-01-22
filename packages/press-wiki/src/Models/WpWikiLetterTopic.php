<?php

namespace Moox\PressWiki\Models;

use Override;
use Illuminate\Database\Eloquent\Builder;
use Moox\Press\Models\WpTerm;

class WpWikiLetterTopic extends WpTerm
{
    #[Override]protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('letter', function (Builder $builder): void {
            $builder->whereHas('termTaxonomy', function ($query): void {
                $query->where('taxonomy', 'letter');
            });
        });
    }
}
