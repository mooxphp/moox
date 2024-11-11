<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Illuminate\Support\Str;
use Moox\Builder\Services\EntityGenerator;
use Moox\Builder\Services\EntityImporter;
use Moox\Builder\Services\EntityService;
use Moox\Builder\Services\MigrationFinder;

class CreateFromMigrationCommand extends AbstractBuilderCommand
{
    protected $signature = 'builder:create-from-migration
        {migration : Path to existing migration file}
        {--app : Generate in App namespace}
        {--package= : Package namespace}
        {--preview : Generate in preview mode}';

    protected $description = 'Create a new entity from an existing migration file';

    public function __construct(
        private readonly EntityService $entityService,
        private readonly EntityGenerator $entityGenerator
    ) {
        parent::__construct();
    }

    public function handle(
        EntityImporter $importer,
        MigrationFinder $finder
    ): int {
        $migrationPath = $this->argument('migration');

        if (! file_exists($migrationPath)) {
            $this->error("Migration file not found: {$migrationPath}");

            return self::FAILURE;
        }

        $blueprint = $finder->extractBlueprintFromFile($migrationPath);
        if (! $blueprint) {
            $this->error('Could not parse migration file');

            return self::FAILURE;
        }

        $modelName = $this->getModelNameFromMigration($migrationPath);
        $package = $this->option('package');
        $preview = $this->option('preview');

        if ($this->option('package') === null && str_contains($migrationPath, 'packages/')) {
            preg_match('/packages\/([^\/]+)\//', $migrationPath, $matches);
            $package = $matches[1] ?? null;
        }

        $buildContext = $preview ? 'preview' : ($package ? 'package' : 'app');

        $result = $this->entityService->create($modelName, $buildContext, 'simple-item');
        $entity = $result['entity'];

        if ($result['status'] === 'exists') {
            if (! $this->confirm("Entity '{$modelName}' already exists in {$buildContext} context. Do you want to rebuild it?")) {
                return self::FAILURE;
            }

            $latestBuild = $this->entityService->getLatestBuild($entity->id);
            if ($latestBuild) {
                if ($buildContext === 'preview') {
                    $this->entityService->cleanupPreviewFiles($latestBuild);
                    $this->entityService->dropPreviewTable($modelName);
                } else {
                    $this->warn('Warning: This entity might have production data.');
                    if (! $this->confirm('Are you sure you want to regenerate files? This might require manual migration handling.')) {
                        return self::FAILURE;
                    }
                }
            }
        }

        $context = $this->createContext($modelName, $package, $preview);

        $blocks = $importer->importFromBlueprint($blueprint);
        $this->entityGenerator->setContext($context);
        $this->entityGenerator->setBlocks($blocks);
        $this->entityGenerator->execute();

        $this->entityService->recordBuild(
            $entity->id,
            $buildContext,
            $blocks,
            $this->entityGenerator->getGeneratedFiles()
        );

        $this->info('Entity '.$modelName.' '.($result['status'] === 'exists' ? 're' : '').'built successfully in '.$buildContext);

        return self::SUCCESS;
    }

    private function getModelNameFromMigration(string $path): string
    {
        $filename = basename($path);

        if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_/', $filename)) {
            $filename = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $filename);
        }

        if (preg_match('/create_(.+)_table/', $filename, $matches)) {
            return Str::studly(Str::singular($matches[1]));
        }

        return Str::studly(str_replace(['.php', '_table', 'create_'], '', $filename));
    }
}
