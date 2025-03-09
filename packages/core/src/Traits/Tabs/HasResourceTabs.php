<?php

namespace Moox\Core\Traits\Tabs;

use Illuminate\Database\Eloquent\Builder;

trait HasResourceTabs
{
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
        if (in_array($currentTab, ['trash', 'deleted'])) {
            return $query;
        }

        $tabsConfig = config(static::getResourceKey().'.tabs', []);

        if (isset($tabsConfig[$currentTab]['query'])) {
            foreach ($tabsConfig[$currentTab]['query'] as $condition) {
                if (! isset($condition['field']) || ! isset($condition['operator'])) {
                    continue;
                }

                $value = $condition['value'] ?? null;

                $query = $query->where(
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
        $className = class_basename(static::class);
        $key = strtolower(str_replace('Resource', '', $className));

        if (str_contains(static::class, 'Builder\\Resources')) {
            return 'previews.'.str_replace('_', '-', $key);
        }

        return $key;
    }
}
