<?php

namespace Moox\Demo\Commands;

use Illuminate\Console\Command;
use Moox\Demo\Jobs\LongJob;

class LongJobCommand extends Command
{
    protected $signature = 'moox:longjob';

    protected $description = 'Start the Moox Long Job';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Starting Moox Long Job');

        LongJob::dispatch();

        $this->info('Moox Long Job finished');
    }
}
