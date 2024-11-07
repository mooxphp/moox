<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Illuminate\Console\Command;

class PrepareAppForPackagesCommand extends Command
{
    protected $signature = 'builder:preparepackages';

    public function handle(): void
    {
        $this->info('Preparing app for packages...');
    }
}
