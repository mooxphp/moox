<?php

namespace Moox\User\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mooxuser:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish Migration and Config. from User Package ';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->comment('Publishing User Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'user-config']);

        $this->comment('Publishing User Migrations...');
        $this->callSilent('vendor:publish', ['--tag' => 'user-migrations']);
        $this->call('migrate');
        $this->info('User was installed successfully');
    }
}
