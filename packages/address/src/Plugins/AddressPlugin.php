<?php

declare(strict_types=1);

namespace Moox\Address\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Address\Resources\AddressResource;
use Moox\Address\Resources\AddressUsageResource;
use Moox\Address\Resources\AddressUsageTypeResource;

class AddressPlugin implements Plugin
{
    public function getId(): string
    {
        return 'address';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            AddressResource::class,
            AddressUsageResource::class,
            AddressUsageTypeResource::class,
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
