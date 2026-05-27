<?php

namespace Moox\Demo\Commands;

use Illuminate\Console\Command;
use Moox\Demo\Jobs\ShortJob;

class ShortJobCommand extends Command
{
    protected $signature = 'moox:shortjob';

    protected $description = 'Start the Moox Short Job';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Starting Moox Short Job');

        ShortJob::dispatch();

        $this->info('Moox Short Job finished');
    }
}
