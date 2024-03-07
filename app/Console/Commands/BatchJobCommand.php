<?php

namespace App\Console\Commands;

use App\Jobs\BatchJob;
use Illuminate\Console\Command;

class BatchJobCommand extends Command
{
    protected $signature = 'moox:batchjob';

    protected $description = 'Start the Moox Batch Job';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Starting Moox Batch Job');

        BatchJob::dispatch();

        $this->info('Moox Batch Job finished');
    }
}
