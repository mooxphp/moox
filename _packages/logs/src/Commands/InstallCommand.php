<?php

namespace Moox\Logs\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mooxlogs:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish Migration and Config. from Logs Package ';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->comment('Publishing Logs Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'logs-config']);

        $this->comment('Publishing Logs Migrations...');
        $this->callSilent('vendor:publish', ['--tag' => 'logs-migrations']);
        $this->call('migrate');
        $this->info('Logs was installed successfully');
    }
}
