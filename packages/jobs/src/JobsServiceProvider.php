<?php

namespace Moox\Jobs;

use Override;
use Moox\Jobs\Commands\InstallCommand;
use Moox\Jobs\Commands\UpdateCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class JobsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('jobs')
            ->hasConfigFile()
            ->hasRoutes('api')
            ->hasTranslations()
            ->hasCommands(InstallCommand::class, UpdateCommand::class);
    }

    #[Override]
    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../database/migrations/01_create_job_manager_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_01_create_job_manager_table.php'),
            ], 'jobs-manager-migration');

            $this->publishes([
                __DIR__.'/../database/migrations/02_create_job_batch_manager_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_02_create_job_batch_manager_table.php'),
            ], 'jobs-batch-migration');

            $this->publishes([
                __DIR__.'/../database/migrations/03_create_job_queue_workers_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_03_create_job_queue_workers_table.php'),
            ], 'jobs-queue-migration');

            $this->publishes([
                __DIR__.'/../database/migrations/04_add_foreigns_to_job_manager_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_04_add_foreigns_to_job_manager_table.php'),
            ], 'jobs-manager-foreigns-migration');
        }
    }
}
