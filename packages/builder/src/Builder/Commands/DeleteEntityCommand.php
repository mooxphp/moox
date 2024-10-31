<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Commands;

use Moox\Builder\Builder\Services\EntityFilesRemover;

class DeleteEntityCommand extends AbstractBuilderCommand
{
    protected $signature = 'builder:delete-entity {name}';

    protected $description = 'Delete an entity and its files';

    public function handle(): void
    {
        $name = $this->argument('name');
        $context = $this->createContext($name);

        (new EntityFilesRemover($context))->execute();

        $this->info("Entity {$name} deleted successfully!");
    }
}
