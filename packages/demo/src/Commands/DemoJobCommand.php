<?php

namespace Moox\Demo\Commands;

use Illuminate\Console\Command;
use Moox\Demo\Jobs\DemoJob;

class DemoJobCommand extends Command
{
    protected $signature = 'moox:demojob';

    protected $description = 'Start the Moox Demo Job';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Starting Moox Demo Job');

        DemoJob::dispatch();

        $this->info('Moox Demo Job finished');
    }
}
