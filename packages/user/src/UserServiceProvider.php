<?php

declare(strict_types=1);

namespace Moox\User;

use Moox\User\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class UserServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('user')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations(['update_user_table'])
            ->hasCommand(InstallCommand::class);
    }
}
