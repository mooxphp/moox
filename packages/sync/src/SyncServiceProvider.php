<?php

declare(strict_types=1);

namespace Moox\Sync;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use Moox\Sync\Commands\InstallCommand;
use Moox\Sync\Http\Middleware\PlatformTokenAuthMiddleware;
use Moox\Sync\Jobs\SyncBackupJob;
use Moox\Sync\Listener\SyncListener;
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

        $this->registerSyncBackupJob();
        $this->registerSyncEloquentListener();
    }

    protected function registerSyncBackupJob()
    {
        $syncBackupJobConfig = Config::get('sync.sync_backup_job');

        if ($syncBackupJobConfig['enabled']) {
            $this->app->booted(function () use ($syncBackupJobConfig) {
                $schedule = $this->app->make(Schedule::class);
                $schedule->job(new SyncBackupJob)->{$syncBackupJobConfig['frequency']}();
            });
        }
    }

    protected function registerSyncEloquentListener()
    {
        $syncEloquentListenerConfig = Config::get('sync.sync_eloquent_listener');

        if ($syncEloquentListenerConfig['enabled']) {
            $syncListener = new SyncListener;
            $syncListener->registerListeners();
        }
    }
}
