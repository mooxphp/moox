<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Commands;

use Moox\Builder\Builder\Services\EntityGenerator;
use Moox\Builder\Builder\Services\PreviewMigrator;

class CreatePreviewCommand extends AbstractBuilderCommand
{
    protected $signature = 'builder:create-preview {name}';

    protected $description = 'Create a preview entity with migrations';

    public function handle(): void
    {
        $name = $this->argument('name');
        $context = $this->createContext($name, preview: true);

        (new EntityGenerator($context, [], []))->execute();
        (new PreviewMigrator($context))->execute();

        $this->info("Preview entity {$name} created successfully!");
    }
}
