<?php

declare(strict_types=1);

namespace Moox\Expiry;

use Moox\Expiry\Commands\EscalatedExpiriesCommand;
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
            ->hasViews('escalated_expiries')
            ->hasMigration('create_expiries_table')
            ->hasRoutes('api')
            ->hasCommands(InstallCommand::class, EscalatedExpiriesCommand::class);
    }

    public function boot()
    {
        parent::boot();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'expiry');
    }

    public function packageRegistered()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
