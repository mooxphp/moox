<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

class ActivatePackageCommand extends AbstractPackageBuilderCommand
{
    protected $signature = 'builder:activatepackage {name}';

    public function handle(): void
    {
        $this->info('Activating package...');
        // activate the package by using the PackageActivator service
    }
}
