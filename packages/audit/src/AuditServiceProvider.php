<?php

declare(strict_types=1);

namespace Moox\Audit;

use Moox\Audit\Commands\InstallCommand;
use Moox\Audit\Observers\ConfigDrivenModelObserver;
use Moox\Audit\Support\AuditBootstrap;
use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class AuditServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('audit')
            ->hasConfigFile()
            ->hasMigrations([
                'create_activity_log_table',
            ])
            ->hasCommand(InstallCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ConfigDrivenModelObserver::class);
    }

    public function packageBooted(): void
    {
        $activityModel = config('audit.activity_model');

        if (is_string($activityModel) && class_exists($activityModel)) {
            config(['activitylog.activity_model' => $activityModel]);
        }

        $this->app->booted(function (): void {
            AuditBootstrap::boot();
        });
    }
}
