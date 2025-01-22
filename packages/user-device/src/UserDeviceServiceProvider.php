<?php

declare(strict_types=1);

namespace Moox\UserDevice;

use Moox\UserDevice\Commands\InstallCommand;
use Moox\UserDevice\Services\LocationService;
use Moox\UserDevice\Services\UserDeviceTracker;
use Override;
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

    #[Override]
    public function register(): void
    {
        parent::register();

        $this->app->singleton(LocationService::class, fn ($app): LocationService => new LocationService);

        $this->app->singleton(UserDeviceTracker::class, fn ($app): UserDeviceTracker => new UserDeviceTracker($app->make(LocationService::class)));
    }
}
