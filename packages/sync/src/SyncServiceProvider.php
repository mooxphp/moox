<?php

declare(strict_types=1);

namespace Moox\Sync;

use Moox\Sync\Commands\InstallCommand;
use Moox\Sync\Http\Middleware\PlatformTokenAuthMiddleware;
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
            ->hasRoute('api')
            ->hasMigrations(['01_create_platforms_table', '02_create_syncs_table', '03_create_user_platform_table'])
            ->hasCommand(InstallCommand::class);
    }

    public function boot()
    {
        parent::boot();

        $this->app->make('router')->aliasMiddleware(
            'auth.platformtoken',
            PlatformTokenAuthMiddleware::class
        );
    }
}
