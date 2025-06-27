<?php

/**
 * @deprecated Use Base classes in Entities instead.
 */

declare(strict_types=1);

namespace Moox\Core\Traits\SoftDelete;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

trait SingleSoftDeleteInModel
{
    use SoftDeletes;

    public function scopeOnlyTrashed(Builder $query): Builder
    {
        return $query->whereNotNull($this->getQualifiedDeletedAtColumn());
    }
}
