<?php

declare(strict_types=1);

namespace Moox\Supplier\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Supplier\Resources\SupplierResource;

class SupplierPlugin implements Plugin
{
    public function getId(): string
    {
        return 'supplier';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            SupplierResource::class,
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
