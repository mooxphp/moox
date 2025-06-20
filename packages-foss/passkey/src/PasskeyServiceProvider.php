<?php

declare(strict_types=1);

namespace Moox\Passkey;

use Moox\Passkey\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PasskeyServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('passkey')
            ->hasConfigFile()
            ->hasViews()
            // ->hasRoute('web')
            ->hasTranslations()
            ->hasMigrations(['create_passkeys_table'])
            ->hasCommand(InstallCommand::class);
    }
}
