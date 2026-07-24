<?php

declare(strict_types=1);

namespace Moox\ProductGroup\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\ProductGroup\Resources\ProductGroupResource;

class ProductGroupPlugin implements Plugin
{
    public function getId(): string
    {
        return 'productgroup';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                ProductGroupResource::class,
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
