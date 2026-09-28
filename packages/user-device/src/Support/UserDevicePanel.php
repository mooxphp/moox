<?php

declare(strict_types=1);

namespace Moox\UserDevice\Support;

final class UserDevicePanel
{
    /**
     * Whether the devices Filament resource is registered on this panel.
     * Trust middleware / mail still run on every panel that has the plugin.
     */
    public static function registersResource(?string $panelId): bool
    {
        if (! filled($panelId)) {
            return false;
        }

        $panels = config('user-device.resource_panels', ['admin']);

        return is_array($panels) && in_array($panelId, $panels, true);
    }
}
