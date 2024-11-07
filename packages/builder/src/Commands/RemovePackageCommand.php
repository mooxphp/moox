<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

class RemovePackageCommand extends AbstractPackageBuilderCommand
{
    protected $signature = 'builder:removepackage {name}';

    public function handle(): void
    {
        $this->info('Removing package...');
    }
}
