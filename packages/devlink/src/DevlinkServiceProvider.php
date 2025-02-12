<?php

declare(strict_types=1);

namespace Moox\Devlink;

use Moox\Devlink\Commands\LinkPackages;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DevlinkServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('devlink')
            ->hasConfigFile();
    }

    public function packageBooted(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                LinkPackages::class,
            ]);
        }
    }
}
