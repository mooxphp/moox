<?php

declare(strict_types=1);

namespace Moox\Category;

use Moox\Category\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CategoryServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('category')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations(['create_categories_table', 'create_categorizables_table'])
            ->hasCommand(InstallCommand::class);
    }
}
