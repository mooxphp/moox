<?php

/**
 * @deprecated Use Base classes in Entities instead.
 */

declare(strict_types=1);

namespace Moox\Core\Traits\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;

trait BaseInViewPage
{
    protected function resolveRecord($key): Model
    {
        $model = static::getModel();

        $query = $model::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $query->findOrFail($key);
    }
}
