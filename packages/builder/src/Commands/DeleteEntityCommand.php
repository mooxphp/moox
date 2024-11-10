<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Moox\Builder\Services\EntityService;

class DeleteEntityCommand extends AbstractBuilderCommand
{
    protected $signature = 'builder:delete-entity {name} {--force} {--package=} {--app}';

    protected $description = 'Delete an entity and its files';

    public function __construct(
        private readonly EntityService $entityService
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $name = $this->argument('name');
        $force = $this->option('force');
        $package = $this->option('package');
        $buildContext = $package ? 'package' : ($this->option('app') ? 'app' : 'preview');

        if (! $force && $buildContext !== 'preview') {
            if (! $this->confirm("Are you sure you want to delete the {$buildContext} entity '{$name}'?")) {
                return;
            }
        }

        $result = $this->entityService->delete($name, $buildContext, $force);

        if ($result['status'] === 'not_found') {
            $this->error("No entity named '{$name}' found in {$buildContext} context.");

            return;
        }

        if ($result['build']) {
            $this->entityService->cleanupPreviewFiles($result['build']);

            if ($buildContext === 'preview') {
                $this->entityService->dropPreviewTable($name);
                $this->info("Dropped preview table for {$name}");
            } else {
                $this->warn('Table was not dropped as it might contain production data.');
            }
        }

        $this->info("Entity '{$name}' deleted successfully from {$buildContext} context!");
    }
}
