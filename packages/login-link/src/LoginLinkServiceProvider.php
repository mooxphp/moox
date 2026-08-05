<?php

declare(strict_types=1);

namespace Moox\LoginLink;

use Moox\Core\MooxServiceProvider;
use Moox\LoginLink\Commands\InstallCommand;
use Moox\LoginLink\Services\RedemptionHandlerRegistry;
use Spatie\LaravelPackageTools\Package;

class LoginLinkServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('login-link')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([
                'create_login_links_table',
                'add_subject_and_process_to_login_links_table',
                'create_login_link_processes_table',
            ])
            ->hasCommand(InstallCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(RedemptionHandlerRegistry::class);
    }
}
