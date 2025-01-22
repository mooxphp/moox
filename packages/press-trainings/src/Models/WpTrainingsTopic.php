<?php

namespace Moox\PressTrainings\Models;

use Override;
use Illuminate\Database\Eloquent\Builder;
use Moox\Press\Models\WpTerm;

class WpTrainingsTopic extends WpTerm
{
    #[Override]protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('schulungen', function (Builder $builder): void {
            $builder->whereHas('termTaxonomy', function ($query): void {
                $query->where('taxonomy', 'schulungen_rubrik');
            });
        });
    }
}
