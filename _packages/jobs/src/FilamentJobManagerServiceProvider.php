<?php

namespace Adrolli\FilamentJobManager;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentJobManagerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('filament-job-manager')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigration('create_filament-job-manager_table')
            ->hasRoutes('web');
    }
}
