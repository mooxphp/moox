<?php

namespace Moox\Core\Traits;

use Closure;
use Illuminate\Database\Eloquent\SoftDeletes;

trait HasQueriesInConfig
{
    protected function applyConditions($query, $conditions)
    {
        foreach ($conditions as $condition) {
            $value = $condition['value'];

            if ($value instanceof Closure) {
                $value = $value();
            }

            if ($condition['field'] === 'deleted_at' && in_array(SoftDeletes::class, class_uses_recursive($query->getModel()))) {
                $query = $query->withTrashed();
            }

            $query = $query->where($condition['field'], $condition['operator'], $value);
        }

        return $query;
    }
}
