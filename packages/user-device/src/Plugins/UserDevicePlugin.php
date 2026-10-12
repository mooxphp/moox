<?php

namespace Moox\UserDevice\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\UserDevice\Http\Middleware\EnsureTrustedDevice;
use Moox\UserDevice\Http\Middleware\SyncDeviceIdToSessionRow;
use Moox\UserDevice\Resources\UserDeviceResource;
use Moox\UserDevice\Support\UserDevicePanel;

class UserDevicePlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'user-device';
    }

    public function register(Panel $panel): void
    {
        // Plugin present on the panel is not enough — master switch must be on.
        if (! config('user-device.enabled', false)) {
            return;
        }

        // Resource is ops UI only (default: admin). Portal keeps middleware, no menu.
        if ($this->shouldRegisterResource($panel)) {
            $panel->resources([
                UserDeviceResource::class,
            ]);
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

    protected function shouldRegisterResource(Panel $panel): bool
    {
        return UserDevicePanel::registersResource($panel->getId());
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
