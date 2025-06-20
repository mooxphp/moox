<?php

declare(strict_types=1);

namespace Moox\LoginLink;

use Moox\LoginLink\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LoginLinkServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('login-link')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasRoute('web')
            ->hasMigrations(['create_login_links_table'])
            ->hasCommand(InstallCommand::class);
    }
}
