<?php

namespace Moox\Core\Traits\Tabs;

use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Traits\TableQueryTrait;

trait TabsInResource
{
    use TableQueryTrait;

    protected static ?string $currentTab = null;

    public static function getCurrentTab(): ?string
    {
        if (static::$currentTab === null) {
            static::$currentTab = request()->query('tab', '');
        }

        return static::$currentTab ?: null;
    }

    public static function setCurrentTab(?string $tab): void
    {
        static::$currentTab = $tab;
    }

    protected static function applyTabQuery(Builder $query, string $currentTab): Builder
    {
        // Skip if this is a soft-delete tab as it's already handled
        if (in_array($currentTab, ['trash', 'deleted'])) {
            return $query;
        }

        // Get tab configuration
        $tabsConfig = config(static::getResourceKey().'.tabs', []);

        if (isset($tabsConfig[$currentTab]['query'])) {
            foreach ($tabsConfig[$currentTab]['query'] as $condition) {
                $value = $condition['value'];

                // Handle closure values
                if (is_string($value) && str_contains($value, 'function')) {
                    $value = eval("return {$value};");
                }

                // Apply configured query conditions
                $query->where(
                    $condition['field'],
                    $condition['operator'],
                    is_callable($value) ? $value() : $value
                );
            }
        }

        return $query;
    }

    protected static function getResourceKey(): string
    {
        // Convert class name to config key
        // e.g., App\Resources\UserResource -> 'user'
        $className = class_basename(static::class);

        return strtolower(str_replace('Resource', '', $className));
    }
}
