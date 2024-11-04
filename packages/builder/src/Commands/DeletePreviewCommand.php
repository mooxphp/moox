<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Moox\Builder\Services\EntityFilesRemover;
use Moox\Builder\Services\EntityTablesRemover;

class DeletePreviewCommand extends AbstractBuilderCommand
{
    protected $signature = 'builder:delete-preview {name}';

    protected $description = 'Delete a preview entity and its database tables';

    public function handle(): void
    {
        $name = $this->argument('name');
        $context = $this->createContext($name, preview: true);

        (new EntityFilesRemover($context))->execute();
        (new EntityTablesRemover($context))->execute();

        $this->info("Preview entity {$name} deleted successfully!");
    }
}
