<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

class CreatePackageCommand extends AbstractPackageBuilderCommand
{
    protected $signature = 'builder:createpackage {name}';

    public function handle(): void
    {
        $this->info('Creating package...');
        // create the package using the PackageGenerator service
    }
}
