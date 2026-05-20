<?php

declare(strict_types=1);

namespace Moox\UserDevice;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Moox\Core\MooxServiceProvider;
use Moox\UserDevice\Commands\InstallCommand;
use Moox\UserDevice\Listeners\TrackUserDeviceOnLogin;
use Moox\UserDevice\Models\UserDevice;
use Moox\UserDevice\Policies\UserDevicePolicy;
use Moox\UserDevice\Services\LocationService;
use Moox\UserDevice\Services\UserDeviceTracker;
use Override;
use Spatie\LaravelPackageTools\Package;

class UserDeviceServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('user-device')
            ->hasRoutes(['web'])
            ->hasConfigFile()
            ->hasTranslations()
            ->hasViews()
            ->hasMigrations([
                'create_user_devices_table',
                'add_device_id_to_sessions_table',
            ])
            ->hasCommand(InstallCommand::class);
    }

    #[Override]
    public function register(): void
    {
        parent::register();

        $this->app->singleton(LocationService::class, fn ($app): LocationService => new LocationService);

        $this->app->singleton(UserDeviceTracker::class, fn ($app): UserDeviceTracker => new UserDeviceTracker($app->make(LocationService::class)));
    }

    public function bootingPackage(): void
    {
        Gate::policy(UserDevice::class, UserDevicePolicy::class);
    }

    public function packageBooted(): void
    {
        if (! config('user-device.enabled', false)) {
            return;
        }

        Event::listen(Login::class, TrackUserDeviceOnLogin::class);
    }
}
