<?php

namespace Moox\Data\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mooxdata:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish and migrate Moox Data Package';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->comment('Publishing Data Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'data-config']);

        $this->comment('Publishing Data Migrations...');
        $this->callSilent('vendor:publish', ['--tag' => 'data-migrations']);
        $this->call('migrate');
        $this->info('Moox Data was installed successfully');
    }
}
