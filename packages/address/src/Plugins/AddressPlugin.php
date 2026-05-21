<?php

namespace Moox\Address\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Address\Resources\AddressResource;
use Moox\Core\Support\Resources\ChildResourceRegistrar;

class AddressPlugin implements Plugin
{
    public function getId(): string
    {
        return 'address';
    }

    public function register(Panel $panel): void
    {
        ChildResourceRegistrar::registerFromParentDefinition(
            $panel,
            AddressResource::class,
            'address',
            config('address.resources.address', []),
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
