<?php

declare(strict_types=1);

namespace Moox\UserSession;

use Moox\UserSession\Commands\InstallCommand;
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
            ->hasMigrations(['create_user_sessions_table'])
            ->hasCommand(InstallCommand::class);
    }
}
