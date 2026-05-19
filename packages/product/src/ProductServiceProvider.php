<?php

declare(strict_types=1);

namespace Moox\Product;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class ProductServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('product')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations(
                'create_products_table',
                'create_product_translations_table',
            )
            ->hasCommands();
    }
}
