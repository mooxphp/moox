<?php

namespace Moox\Blog\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mooxblog:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish Migration and Config. from Blog Package ';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->comment('Publishing Blog Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'blog-config']);

        $this->comment('Publishing Blog Migrations...');
        $this->callSilent('vendor:publish', ['--tag' => 'blog-migrations']);
        $this->call('migrate');
        $this->info('Blog was installed successfully');
    }
}
