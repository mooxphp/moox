<?php

declare(strict_types=1);

namespace Moox\UserDevice;

use Moox\UserDevice\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class UserDeviceServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('user-device')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations(['create_user_devices_table'])
            ->hasCommand(InstallCommand::class);
    }
}
