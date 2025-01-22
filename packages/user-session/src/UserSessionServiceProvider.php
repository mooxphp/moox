<?php

declare(strict_types=1);

namespace Moox\UserSession;

use Override;
use Moox\UserSession\Commands\InstallCommand;
use Moox\UserSession\Services\SessionRelationService;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class UserSessionServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('user-session')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasCommand(InstallCommand::class);
    }

    #[Override]
    public function register(): void
    {
        parent::register();

        $this->app->singleton(SessionRelationService::class, fn($app): SessionRelationService => new SessionRelationService);
    }

    #[Override]
    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_sessions_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_01_create_sessions_table.php'),
            ], 'create-sessions-table');

            $this->publishes([
                __DIR__.'/../database/migrations/extend_sessions_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_02_extend_sessions_table.php'),
            ], 'extend-sessions-table');
        }
    }
}
