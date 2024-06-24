<?php

namespace Moox\UserDevice;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
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
