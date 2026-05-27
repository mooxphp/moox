<?php

namespace Moox\Demo\Commands;

use Illuminate\Console\Command;
use Moox\Demo\Jobs\FailJob;

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
