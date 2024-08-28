<?php

namespace Moox\PressTrainings\Models;

use Illuminate\Database\Eloquent\Builder;
use Moox\Press\Models\WpTerm;

class WpTrainingsTopic extends WpTerm
{
    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('schulungen', function (Builder $builder) {
            $builder->whereHas('termTaxonomy', function ($query) {
                $query->where('taxonomy', 'schulungen_rubrik');
            });
        });
    }
}
