<?php

namespace App\Console\Commands;

use App\Jobs\TimeoutJob;
use Illuminate\Console\Command;

class TimeoutJobCommand extends Command
{
    protected $signature = 'moox:timeoutjob';

    protected $description = 'Start the Moox Timeout Job';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Starting Moox Timeout Job');

        TimeoutJob::dispatch();

        $this->info('Moox Timeout Job finished');
    }
}
