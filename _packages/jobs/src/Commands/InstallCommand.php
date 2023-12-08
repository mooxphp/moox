<?php

namespace Moox\Jobs\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mooxjobs:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish and migrate Moox Jobs Package';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->comment('Publishing Jobs Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'jobs-config']);

        $this->comment('Publishing Jobs Migrations...');
        $this->callSilent('vendor:publish', ['--tag' => 'jobs-migrations']);

        if (config('queue.default') === 'database') {
            $this->comment('Creating Queue Tables...');
            $this->callSilent('queue:table');
            $this->callSilent('queue:failed-table');
            $this->callSilent('queue:batches-table');
        }

        if (class_exists('Adrolli\FilamentJobManager\ServiceProvider')) {
            $this->comment('Uninstalling adrolli/filament-job-manager package...');
            $this->callSilent('composer', ['remove', 'adrolli/filament-job-manager']);
            $this->comment('adrolli/filament-job-manager package uninstalled successfully.');
        }

        $this->call('migrate');
        $this->info('Moox Jobs was installed successfully');
    }
}
