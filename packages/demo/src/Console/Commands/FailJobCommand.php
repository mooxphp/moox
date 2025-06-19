<?php

namespace App\Console\Commands;

use App\Jobs\FailJob;
use Illuminate\Console\Command;

class FailJobCommand extends Command
{
    protected $signature = 'moox:failjob';

    protected $description = 'Start the Moox Fail Job';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Starting Moox Fail Job');

        FailJob::dispatch();

        $this->info('Moox Fail Job finished');
    }
}
