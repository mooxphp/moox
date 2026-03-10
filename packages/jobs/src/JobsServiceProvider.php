<?php

namespace Moox\Jobs;

use Moox\Core\MooxServiceProvider;
use Moox\Jobs\Commands\InstallCommand;
use Moox\Jobs\Commands\UpdateCommand;
use Spatie\LaravelPackageTools\Package;

class JobsServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package->name('jobs')
            ->hasConfigFile()
            ->hasRoutes('api')
            ->hasMigrations(['01_create_job_manager_table', '02_create_job_batch_manager_table', '03_create_job_queue_workers_table', '04_add_foreigns_to_job_manager_table'])
            ->hasTranslations()
            ->hasCommands(InstallCommand::class, UpdateCommand::class);
    }
}
