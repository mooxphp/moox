<?php

declare(strict_types=1);

namespace Moox\Expiry;

use Moox\Expiry\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ExpiryServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('expiry')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_expiries_table')
            ->hasTranslations()
            ->hasRoutes('api')
            ->hasCommands(InstallCommand::class);
    }

    public function packageRegistered()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
