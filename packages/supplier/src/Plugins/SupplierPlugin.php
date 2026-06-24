<?php

declare(strict_types=1);

namespace Moox\Supplier\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Core\Support\Resources\ChildResourceRegistrar;
use Moox\Supplier\Resources\SupplierResource;

class SupplierPlugin implements Plugin
{
    public function getId(): string
    {
        return 'supplier';
    }

    public function register(Panel $panel): void
    {
        ChildResourceRegistrar::registerFromParentDefinition(
            $panel,
            SupplierResource::class,
            'supplier',
            config('supplier.resources.supplier', []),
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
