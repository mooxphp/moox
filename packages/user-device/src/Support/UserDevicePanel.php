<?php

declare(strict_types=1);

namespace Moox\UserDevice\Support;

use Filament\Facades\Filament;
use Filament\Panel;
use Throwable;

final class UserDevicePanel
{
    /**
     * Device logic (track, mail, trust middleware) is active only when
     * user-device.enabled is true AND UserDevicePlugin is on this panel.
     */
    public static function isActive(?Panel $panel = null): bool
    {
        if (! config('user-device.enabled', false)) {
            return false;
        }

        $panel ??= self::currentPanel();

        return $panel !== null && $panel->hasPlugin('user-device');
    }

    /**
     * Whether the devices Filament resource is registered on this panel.
     * Independent of trust/mail — only UI. Requires {@see isActive()} too
     * because the plugin registers the resource only when enabled.
     */
    public static function registersResource(?string $panelId): bool
    {
        if (! filled($panelId)) {
            return false;
        }

        $panels = config('user-device.resource_panels', ['admin']);

        return is_array($panels) && in_array($panelId, $panels, true);
    }

    protected static function currentPanel(): ?Panel
    {
        if (! class_exists(Filament::class)) {
            return null;
        }

        try {
            return Filament::getCurrentPanel();
        } catch (Throwable) {
            return null;
        }
    }
}
