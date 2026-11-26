<?php

declare(strict_types=1);

namespace Moox\ProductCategory;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class ProductCategoryServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('productcategory')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations(
                'create_productcategories_table',
                'create_productcategory_translations_table',
            );
    }
}
