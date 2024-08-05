<?php

namespace Moox\Core\Traits;

trait QueriesInConfig
{
    protected function applyConditions($query, $conditions)
    {
        foreach ($conditions as $condition) {
            $query = $query->where($condition['field'], $condition['operator'], $condition['value']);
        }

        return $query;
    }
}
