<?php

declare(strict_types=1);

namespace Moox\Permission;

use Moox\Permission\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PermissionServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('permission')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations(['create_permissions_table'])
            ->hasCommand(InstallCommand::class);
    }
}
