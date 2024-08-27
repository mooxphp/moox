<?php

declare(strict_types=1);

namespace Moox\UserDevice;

use Moox\UserDevice\Commands\InstallCommand;
use Moox\UserDevice\Services\LocationService;
use Moox\UserDevice\Services\UserDeviceTracker;
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

    public function register()
    {
        parent::register();

        $this->app->singleton(LocationService::class, function ($app) {
            return new LocationService;
        });

        $this->app->singleton(UserDeviceTracker::class, function ($app) {
            return new UserDeviceTracker($app->make(LocationService::class));
        });
    }
}
