<?php

declare(strict_types=1);

namespace Moox\Product\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use LogicException;
use Moox\Product\Resources\ProductResource;
use Moox\Product\Resources\VariantResource;

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
                VariantResource::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        $plugin = app(static::class);

        if (! $plugin instanceof static) {
            throw new LogicException('Product plugin could not be resolved.');
        }

        return $plugin;
    }
}
