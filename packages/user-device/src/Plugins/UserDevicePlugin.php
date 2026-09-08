<?php

namespace Moox\UserDevice\Plugins;

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
        // Origin only — scoped children appear under User (source), not here.
        $panel->resources([
            UserDeviceResource::class,
        ]);

        if (! config('user-device.enabled', false)) {
            return;
        }

        $middleware = [
            SyncDeviceIdToSessionRow::class,
        ];

        if (config('user-device.enforce_trust', true)) {
            $middleware[] = EnsureTrustedDevice::class;
        }

        // Must be persistent so it also runs for Livewire requests (Filament actions/forms).
        $panel->authMiddleware($middleware, isPersistent: true);
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
