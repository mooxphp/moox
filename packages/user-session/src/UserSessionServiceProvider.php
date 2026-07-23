<?php

declare(strict_types=1);

namespace Moox\UserSession;

use Moox\Core\MooxServiceProvider;
use Moox\UserSession\Commands\InstallCommand;
use Moox\UserSession\Services\SessionRelationService;
use Override;
use Spatie\LaravelPackageTools\Package;

class UserSessionServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('user-session')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations([
                'extend_sessions_table',
            ])
            ->hasCommand(InstallCommand::class);
    }

    #[Override]
    public function register(): void
    {
        parent::register();

        $this->app->singleton(SessionRelationService::class, fn ($app): SessionRelationService => new SessionRelationService);
    }
}
