<?php

namespace Usetall\TalluiPackageBuilder;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Usetall\TalluiPackageBuilder\Commands\TalluiPackageBuilderCommand;

class TalluiPackageBuilderServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('tallui-package-builder')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_tallui-package-builder_table')
            ->hasCommand(TalluiPackageBuilderCommand::class);
    }
}
