<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Illuminate\Support\Str;
use Moox\Builder\Services\EntityImporter;
use Moox\Builder\Services\MigrationFinder;

class CreateFromMigrationCommand extends AbstractBuilderCommand
{
    protected $signature = 'builder:create-from-migration
        {migration : Path to existing migration file}
        {--app : Generate in App namespace}
        {--package : Generate in Package namespace}
        {--preview : Generate in preview mode}';

    protected $description = 'Create a new entity from an existing migration file';

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

        $this->info("Creating entity {$modelName} from migration: {$migrationPath}");

        $blocks = $importer->importFromBlueprint($blueprint);

        $this->setupContext($modelName);

        $this->generateEntity($blocks, skipMigration: true);

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

        // Fallback
        return Str::studly(str_replace(['.php', '_table', 'create_'], '', $filename));
    }
}
