<?php

namespace Moox\Devlink\Console\Traits;

trait Finalize
{
    private function finalize(): void
    {
        if (! $this->errorMessage) {
            if ($this->confirm('Run composer update now?', true)) {
                $output = [];
                $returnVar = 0;
                exec('composer update 2>&1', $output, $returnVar);

                if ($returnVar !== 0) {
                    $this->error('Composer update failed: '.implode("\n", $output));

                    return;
                }

                $this->info('Composer update completed successfully');
            } else {
                $this->info("Please run 'composer update' manually");
            }

            if ($this->confirm('Run artisan optimize:clear now?', true)) {
                $this->info('Clearing cache...');
                $this->call('optimize:clear');
                $this->info('Cache cleared successfully');
            } else {
                $this->info("Please run 'artisan optimize:clear' manually");
            }

            if ($this->confirm('Run queue:restart now?', false)) {
                $this->info('Restarting queue...');
                $this->call('queue:restart');
            }
        }
    }
}
