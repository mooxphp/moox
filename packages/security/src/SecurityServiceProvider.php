<?php

declare(strict_types=1);

namespace Moox\Security;

use Moox\Security\Commands\GetPasswordResetLinksCommand;
use Moox\Security\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SecurityServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('security')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations(['extend_password_reset_tokens_table'])
            ->hasCommands(InstallCommand::class, GetPasswordResetLinksCommand::class);
    }
}
