<?php

namespace Moox\Data\Console\Commands;

use Illuminate\Console\Command;
use Moox\Data\Jobs\ImportStaticDataJob;

class ImportStaticDataCommand extends Command
{
    protected $signature = 'moox:data:import-static';

    protected $description = 'Import static data from REST Countries API';

    public function handle()
    {
        ImportStaticDataJob::dispatch();
    }
}
