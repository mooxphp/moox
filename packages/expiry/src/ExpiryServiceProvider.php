<?php

declare(strict_types=1);

namespace Moox\Expiry;

use Moox\Core\Traits\TranslatableConfig;
use Moox\Expiry\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ExpiryServiceProvider extends PackageServiceProvider
{
    use TranslatableConfig;

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

    public function boot()
    {
        parent::boot();

        $this->translateConfigurations();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'expiry');
    }

    public function packageRegistered()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }

    protected function translateConfigurations()
    {
        $translatedConfig = $this->translateConfig(config('expiry'));
        config(['expiry' => $translatedConfig]);
    }
}
