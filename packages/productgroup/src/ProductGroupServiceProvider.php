<?php

declare(strict_types=1);

namespace Moox\ProductGroup;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class ProductGroupServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('productgroup')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations(
                'create_productgroups_table',
                'create_productgroup_translations_table',
            );
    }
}
