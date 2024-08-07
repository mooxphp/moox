<?php

declare(strict_types=1);

namespace Moox\Audit;

use Moox\Audit\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AuditServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('audit')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations(['create_activity_log_table'])
            ->hasCommand(InstallCommand::class);
    }
}
