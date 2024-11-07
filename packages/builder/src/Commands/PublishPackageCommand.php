<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

class PublishPackageCommand extends AbstractPackageBuilderCommand
{
    protected $signature = 'builder:publishpackage {name}';

    public function handle(): void
    {
        $this->info('Publishing package...');
        // use the services from the config array package_entity_publisher to publish the package
    }
}
