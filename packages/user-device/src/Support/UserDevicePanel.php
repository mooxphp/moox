<?php

declare(strict_types=1);

namespace Moox\UserDevice\Support;

use Filament\Facades\Filament;
use Filament\Panel;
use STS\FilamentImpersonate\Facades\Impersonation;
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

        if (self::isImpersonating()) {
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

    /**
     * Panel IDs that register UserDevicePlugin (tracking sources), sorted.
     *
     * @return list<string>
     */
    public static function trackingPanelIds(): array
    {
        if (! class_exists(Filament::class)) {
            return [];
        }

        $ids = [];

        foreach (Filament::getPanels() as $panel) {
            if ($panel->hasPlugin('user-device')) {
                $ids[] = $panel->getId();
            }
        }

        sort($ids);

        return $ids;
    }

    /**
     * Auth model classes whose devices belong to this panel.
     * Resolves the panel guard's provider model, plus optional extras from
     * `user-device.panel_user_types.{panelId}`.
     *
     * @return list<class-string>
     */
    public static function authModelsForPanel(string $panelId): array
    {
        $models = [];

        if (class_exists(Filament::class)) {
            try {
                $panel = Filament::getPanel($panelId);
                $guard = $panel->getAuthGuard();
                $provider = config("auth.guards.{$guard}.provider");
                $model = is_string($provider)
                    ? config("auth.providers.{$provider}.model")
                    : null;

                if (is_string($model) && $model !== '' && class_exists($model)) {
                    $models[] = $model;
                }
            } catch (Throwable) {
                // Panel may not exist in this app.
            }
        }

        $extra = config("user-device.panel_user_types.{$panelId}", []);

        if (is_array($extra)) {
            foreach ($extra as $model) {
                if (is_string($model) && $model !== '' && class_exists($model)) {
                    $models[] = $model;
                }
            }
        }

        return array_values(array_unique($models));
    }

    /**
     * Icon for the admin devices tab of this panel.
     * Set via `user-device.panel_tab_icons.{panelId}`; falls back to devices icon.
     */
    public static function tabIconForPanel(string $panelId): string
    {
        $fromConfig = config("user-device.panel_tab_icons.{$panelId}");

        if (is_string($fromConfig) && $fromConfig !== '') {
            return $fromConfig;
        }

        return 'gmdi-devices-o';
    }

    protected static function isImpersonating(): bool
    {
        if (! class_exists(Impersonation::class)) {
            return false;
        }

        try {
            return (bool) Impersonation::isImpersonating();
        } catch (Throwable) {
            return false;
        }
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
