<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Illuminate\Console\Command;
use Moox\Builder\Builder\Actions\GenerateEntity;
use Moox\Builder\Builder\Actions\PreviewEntity;

class BuildTestEntityCommand extends Command
{
    protected $signature = 'builder:build-test-entity {entityName} {--app} {--package}';

    protected $description = 'Build a test entity with specified blocks and features';

    public function handle(): void
    {
        $entityName = $this->argument('entityName');
        $entityNamespace = $this->option('app') ? 'App' : 'Package';
        $entityPath = $this->option('app') ? app_path() : base_path('packages');

        $blocks = $this->getBlocks();
        $features = $this->getFeatures();

        $generateEntity = new GenerateEntity($entityName, $entityNamespace, $entityPath, $blocks, $features);
        $generateEntity->execute();

        $previewEntity = new PreviewEntity($entityName, $entityNamespace, $entityPath);
        $previewEntity->execute();

        $this->info('Entity generated and previewed successfully.');
    }

    protected function getBlocks(): array
    {
        // Logic to toggle and return selected blocks
        return [];
    }

    protected function getFeatures(): array
    {
        // Logic to toggle and return selected features
        return [];
    }
}
