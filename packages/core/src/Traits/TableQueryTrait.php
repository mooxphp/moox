<?php

namespace Moox\Core\Traits;

use Illuminate\Database\Eloquent\Builder;

trait TableQueryTrait
{
    public static function getTableQuery(): Builder
    {
        $query = method_exists(parent::class, 'getTableQuery')
            ? parent::getTableQuery()
            : static::getModel()::query();

        if (method_exists(static::class, 'applySoftDeleteQuery')) {
            $query = static::applySoftDeleteQuery($query);
        }

        if ($currentTab = request()->query('tab')) {
            if (method_exists(static::class, 'applyTabQuery')) {
                $query = static::applyTabQuery($query, $currentTab);
            }
        }

        // Wildcard modifier, need to implement a method like `myFeatureModifyTableQuery`
        $methods = array_filter(get_class_methods(static::class), function ($method) {
            return str_ends_with($method, 'ModifyTableQuery')
                && ! in_array($method, ['applySoftDeleteQuery', 'applyTabQuery']);
        });

        foreach ($methods as $method) {
            $query = static::$method($query);
        }

        return $query;
    }
}
