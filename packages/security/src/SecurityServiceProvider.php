<?php

declare(strict_types=1);

namespace Moox\Security;

use Illuminate\Auth\Passwords\PasswordResetServiceProvider as LaravelPasswordResetServiceProvider;
use Moox\Core\MooxServiceProvider;
use Moox\Security\Commands\GetPasswordResetLinksCommand;
use Moox\Security\Commands\InstallCommand;
use Override;
use Spatie\LaravelPackageTools\Package;

class SecurityServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
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

        $this->app->registerDeferredProvider(LaravelPasswordResetServiceProvider::class);

        $this->app->register(PasswordResetServiceProvider::class);
    }
}
