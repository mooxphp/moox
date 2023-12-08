<?php

namespace Moox\Core\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mooxcore:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish and migrate Moox Core Package';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->comment('Publishing Core Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'core-config']);

        $this->comment('Publishing Core Migrations...');
        $this->callSilent('vendor:publish', ['--tag' => 'core-migrations']);
        $this->call('migrate');
        $this->info('Moox Core was installed successfully');
    }
}
