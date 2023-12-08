<?php

namespace Moox\Page\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mooxpage:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish and migrate Moox Page Package';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->comment('Publishing Page Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'page-config']);

        $this->comment('Publishing Page Migrations...');
        $this->callSilent('vendor:publish', ['--tag' => 'page-migrations']);
        $this->call('migrate');
        $this->info('Page was installed successfully');
    }
}
