<?php

namespace Moox\Core\Traits;

use Illuminate\Database\Eloquent\Builder;

trait TabsInResource
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

    public static function getTableQuery(?string $currentTab = null): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes();

        $currentTab = $currentTab ?? static::getCurrentTab();

        // TODO: Published is not a tab, it's a status, how to handle this in the traits?
        // Current solution is to handle it in the resource
        // if ($currentTab === 'published') {
        //     $query->where('status', 'published');
        // }

        return $query;
    }
}
