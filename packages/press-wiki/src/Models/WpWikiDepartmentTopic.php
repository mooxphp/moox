<?php

namespace Moox\PressWiki\Models;

use Override;
use Illuminate\Database\Eloquent\Builder;
use Moox\Press\Models\WpTerm;

class WpWikiDepartmentTopic extends WpTerm
{
    #[Override]
    public static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('department', function (Builder $builder): void {
            $builder->whereHas('termTaxonomy', function ($query): void {
                $query->where('taxonomy', 'bereiche');
            });
        });
    }
}
