<?php

declare(strict_types=1);

namespace Moox\Builder;

use Moox\Builder\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class BuilderServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('builder')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([
                'create_items_table',
                'create_full_items_table',
                'create_simple_items_table',
                'create_simple_taxonomies_table',
                'create_nested_taxonomies_table',
                'create_simple_taxonomyables_table',
                'create_nested_taxonomyables_table',
            ])
            ->hasCommand(InstallCommand::class);
    }
}
