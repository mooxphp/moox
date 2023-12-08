<?php

namespace Moox\Builder\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mooxbuilder:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish and migrate Moox Builder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->comment('Publishing Builder Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'builder-config']);

        $this->comment('Publishing Builder Migrations...');
        $this->callSilent('vendor:publish', ['--tag' => 'builder-migrations']);
        $this->call('migrate');
        $this->info('Moox Builder was installed successfully');
    }
}
