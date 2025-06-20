<?php

namespace Moox\Press\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Override;

class WpTag extends WpTerm
{
    protected $taxonomy = 'tag';

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('tag', function (Builder $builder): void {
            $builder->whereHas('termTaxonomy', function ($query): void {
                $query->where('taxonomy', 'post_tag');
            });
        });
    }

    public function termTaxonomy(): HasOne
    {
        return $this->hasOne(WpTermTaxonomy::class, 'term_id', 'term_id');
    }
}
