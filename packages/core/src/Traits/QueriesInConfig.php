<?php

namespace Moox\Core\Traits;

trait QueriesInConfig
{
    protected function applyConditions($query, $conditions)
    {

        // TODO: If the condition value is a class method, use method_exists to check if the method exists
        // If it does, return the query
        // If it doesn't, return null
        // Bad idea, but closure might work
        foreach ($conditions as $condition) {
            $value = $condition['value'];

            if ($value instanceof \Closure) {
                $value = $value();
            }

            $query = $query->where($condition['field'], $condition['operator'], $value);
        }

        return $query;
    }
}
