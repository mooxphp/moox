<?php

namespace Moox\Jobs;

use Moox\Jobs\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class JobsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('jobs')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigration('create_job_manager_table')
            ->hasCommand(InstallCommand::class);
    }
}
