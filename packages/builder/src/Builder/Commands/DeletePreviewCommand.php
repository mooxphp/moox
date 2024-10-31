<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Commands;

use Moox\Builder\Builder\Services\EntityFilesRemover;

class DeletePreviewCommand extends AbstractBuilderCommand
{
    protected $signature = 'builder:delete-preview {name}';

    protected $description = 'Delete a preview entity and its database tables';

    public function handle(): void
    {
        $name = $this->argument('name');
        $context = $this->createContext($name, preview: true);

        (new EntityFilesRemover($context))->execute();

        $this->info("Preview entity {$name} deleted successfully!");
    }
}
