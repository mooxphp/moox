<?php

declare(strict_types=1);

namespace Moox\Core\Services;

class TabStateManager
{
    protected static ?string $currentTab = null;

    public static function getCurrentTab(): ?string
    {
        return request()->query('activeTab', '');
    }

    public static function setCurrentTab(?string $tab): void
    {
        static::$currentTab = $tab;
    }
}
