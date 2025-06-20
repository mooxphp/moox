<?php

namespace Moox\Data\Console\Commands;

use Illuminate\Console\Command;
use Moox\Data\Jobs\ImportStaticDataJob;

class ImportStaticDataCommand extends Command
{
    protected $signature = 'moox:data:import-static {--sync : Run the import synchronously}';

    protected $description = 'Import static data from REST Countries API';

    public function handle()
    {
        $this->info('Starting static data import...');

        if ($this->option('sync')) {
            $this->info('Running import synchronously...');
            (new ImportStaticDataJob)->handle();
            $this->info('Import completed!');
        } else {
            $this->info('Dispatching import job to queue...');
            ImportStaticDataJob::dispatch();
            $this->info('Job dispatched! Make sure your queue worker is running.');
        }
    }
}
