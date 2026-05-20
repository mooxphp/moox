<?php

declare(strict_types=1);

namespace Moox\Product\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Product\Resources\ProductResource;

class ProductPlugin implements Plugin
{
    public function getId(): string
    {
        return 'product';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                ProductResource::class,
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
