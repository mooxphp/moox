<?php

namespace Moox\Core\Traits;

trait QueriesInConfig
{
    protected function applyConditions($query, $conditions)
    {
        foreach ($conditions as $condition) {
            $value = $condition['value'];

            if ($value instanceof \Closure) {
                $value = $value();
            }
            if ($condition['field'] === 'deleted_at') {
                $query = $query->withTrashed();
            }

            $query = $query->where($condition['field'], $condition['operator'], $value);
        }

        return $query;
    }
}
