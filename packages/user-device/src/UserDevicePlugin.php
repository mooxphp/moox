<?php

namespace Moox\UserDevice;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Core\Support\Resources\ChildResourceRegistrar;
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
        ChildResourceRegistrar::registerFromParentDefinition(
            $panel,
            UserDeviceResource::class,
            'user-device',
            config('user-device.resources.devices', []),
        );
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
