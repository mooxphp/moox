<?php

declare(strict_types=1);

namespace Moox\ProductCategory\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use LogicException;
use Moox\ProductCategory\Resources\ProductCategoryResource;

class ProductCategoryPlugin implements Plugin
{
    public function getId(): string
    {
        return 'productcategory';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                ProductCategoryResource::class,
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
            throw new LogicException('Product category plugin could not be resolved.');
        }

        return $plugin;
    }
}
