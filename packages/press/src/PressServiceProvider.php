<?php

declare(strict_types=1);

namespace Moox\Press;

use Moox\Press\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PressServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('press')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasCommands(
                InstallCommand::class,
            );
    }
}
