<?php

namespace Moox\PressTrainings\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Moox\Press\Models\WpTerm;
use Moox\Press\Models\WpTermTaxonomy;
use Override;

class WpTrainingsTopic extends WpTerm
{
    public function termTaxonomy(): HasOne
    {
        return $this->hasOne(WpTermTaxonomy::class, 'term_id');
    }

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('schulungen', function (Builder $builder): void {
            $builder->whereHas('termTaxonomy', function ($query): void {
                $query->where('taxonomy', 'schulungen_rubrik');
            });
        });
    }
}
