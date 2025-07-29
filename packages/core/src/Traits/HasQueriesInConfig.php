<?php

namespace Moox\Core\Traits;

use Closure;
use Illuminate\Database\Eloquent\SoftDeletes;

trait HasQueriesInConfig
{
    protected function applyConditions($query, $conditions)
    {
        foreach ($conditions as $condition) {
            $value = $condition['value'] instanceof Closure
                ? $condition['value']()
                : $condition['value'];

            if (isset($condition['relation'])) {
                // Wenn eine Relation angegeben ist (z. B. translations)
                $query = $query->whereHas($condition['relation'], function ($q) use ($condition, $value) {
                    if (
                        $condition['field'] === 'deleted_at' &&
                        in_array(SoftDeletes::class, class_uses_recursive($q->getModel()))
                    ) {
                        $q->withTrashed();
                    }

                    $q->where($condition['field'], $condition['operator'], $value);
                });
            } else {
                if (
                    $condition['field'] === 'deleted_at' &&
                    in_array(SoftDeletes::class, class_uses_recursive($query->getModel()))
                ) {
                    $query = $query->withTrashed();
                }

                $query = $query->where($condition['field'], $condition['operator'], $value);
            }
        }

        return $query;
    }

}
