<?php

declare(strict_types=1);

namespace Moox\UserDevice;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Moox\UserDevice\Commands\InstallCommand;
use Moox\UserDevice\Listeners\StoreUserDevice;
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

    public function boot()
    {
        parent::boot();

        Event::listen(
            Login::class,
            [StoreUserDevice::class, 'handle']
        );
    }
}
