<?php

declare(strict_types=1);

namespace Moox\Security;

use Illuminate\Auth\Passwords\PasswordResetServiceProvider;
use Moox\Security\Commands\GetPasswordResetLinksCommand;
use Moox\Security\Commands\InstallCommand;
use Override;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SecurityServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('security')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations(['extend_password_reset_tokens_table'])
            ->hasCommands(InstallCommand::class, GetPasswordResetLinksCommand::class);
    }

    #[Override]
    public function register(): void
    {
        parent::register();

        $this->app->registerDeferredProvider(PasswordResetServiceProvider::class);

        $this->app->register(\Moox\Security\PasswordResetServiceProvider::class);
    }
}
