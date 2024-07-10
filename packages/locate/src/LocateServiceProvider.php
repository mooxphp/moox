<?php

declare(strict_types=1);

namespace Moox\Locate;

use Moox\Locate\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LocateServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('locate')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations(['create_locates_table'])
            ->hasCommand(InstallCommand::class);
    }
}
