<?php

declare(strict_types=1);

namespace Moox\LoginLink;

use Moox\Core\MooxServiceProvider;
use Moox\LoginLink\Commands\InstallCommand;
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
            ->hasMigrations(['create_login_links_table'])
            ->hasCommand(InstallCommand::class);
    }
}
