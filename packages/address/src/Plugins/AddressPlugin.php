<?php

declare(strict_types=1);

namespace Moox\Address\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Address\Resources\AddressResource;

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
