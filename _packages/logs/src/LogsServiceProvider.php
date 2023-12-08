<?php

declare(strict_types=1);

namespace Moox\Logs;

use Moox\Logs\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LogsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('logs')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigration('create_logs_table')
            ->hasCommand(InstallCommand::class);
    }
}
