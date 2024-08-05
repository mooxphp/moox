<?php

namespace Moox\Core\Traits;

trait QueriesInConfig
{
    protected function applyParsedQuery($query, $rawQuery)
    {
        $queries = explode('->', $rawQuery);

        foreach ($queries as $queryPart) {
            if (preg_match('/(\w+)\((.*)\)/', $queryPart, $matches)) {
                $method = $matches[1];
                $params = $matches[2];
                $params = str_getcsv($params, ',', '"');
                $params = array_map(fn ($param) => trim($param, '"'), $params);

                if (method_exists($query, $method)) {
                    $query = $query->{$method}(...$params);
                } else {
                    throw new \Exception("Method {$method} does not exist on the query builder.");
                }
            }
        }

        return $query;
    }
}
