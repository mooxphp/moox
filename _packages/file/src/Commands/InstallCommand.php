<?php

namespace Moox\File\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mooxfile:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish Migration and Config. from File Package ';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->comment('Publishing File Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'file-config']);

        $this->comment('Publishing File Migrations...');
        $this->callSilent('vendor:publish', ['--tag' => 'file-migrations']);
        $this->call('migrate');
        $this->info('File was installed successfully');
    }
}
