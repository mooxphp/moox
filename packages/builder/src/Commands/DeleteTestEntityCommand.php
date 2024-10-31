<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Illuminate\Console\Command;
use Moox\Builder\Builder\Actions\CleanupPreview;

class DeleteTestEntityCommand extends Command
{
    protected $signature = 'mooxbuilder:cleanup';

    protected $description = 'Clean up the test entity and its preview';

    public function handle(): void
    {
        $cleanup = new CleanupPreview(
            entityName: 'Blub',
            entityPath: app_path(),
        );
        $cleanup->execute();

        $this->info('Entity and preview cleaned up successfully.');
    }
}
