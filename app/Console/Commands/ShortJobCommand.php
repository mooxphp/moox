<?php

namespace App\Console\Commands;

use App\Jobs\ShortJob;
use Illuminate\Console\Command;

class ShortJobCommand extends Command
{
    protected $signature = 'moox:shortjob';

    protected $description = 'Start the Moox Short Job';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Starting Moox Short Job');

        ShortJob::dispatch();

        $this->info('Moox Short Job finished');
    }
}
