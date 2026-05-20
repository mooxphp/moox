<?php

namespace Moox\UserDevice;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\UserDevice\Http\Middleware\EnsureTrustedDevice;
use Moox\UserDevice\Http\Middleware\SyncDeviceIdToSessionRow;
use Moox\UserDevice\Resources\UserDeviceResource;

class UserDevicePlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'user-device';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            UserDeviceResource::class,
        ]);

        if (! config('user-device.enabled', false)) {
            return;
        }

        // Apply enforcement automatically for panels using this plugin.
        // Must be persistent so it also runs for Livewire requests (Filament actions/forms).
        $panel->authMiddleware([
            SyncDeviceIdToSessionRow::class,
            EnsureTrustedDevice::class,
        ], isPersistent: true);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
