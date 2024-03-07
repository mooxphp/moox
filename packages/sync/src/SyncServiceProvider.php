<?php

declare(strict_types=1);

namespace Moox\Sync;

use Moox\Sync\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SyncServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('sync')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations(['01_create_platforms_table', '02_create_syncs_table'])
            ->hasCommand(InstallCommand::class);
    }
}
