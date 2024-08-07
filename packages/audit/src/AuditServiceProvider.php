<?php

declare(strict_types=1);

namespace Moox\Audit;

use Moox\Audit\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AuditServiceProvider extends PackageServiceProvider
{
    public $name = 'audit';

    public function configurePackage(Package $package): void
    {
        $package
            ->name($this->name)
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations(['create_activity_log_table'])
            ->hasCommand(InstallCommand::class);
    }
}
