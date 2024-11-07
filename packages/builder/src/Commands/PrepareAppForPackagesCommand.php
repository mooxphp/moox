<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Illuminate\Console\Command;

class PrepareAppForPackagesCommand extends Command
{
    protected $signature = 'builder:prepare';

    public function handle(): void
    {
        $this->info('Preparing app for packages...');
        // create packages directory
        // paste composerrepos.stub into composer.json
    }
}
