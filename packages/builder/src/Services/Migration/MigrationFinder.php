<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Migration;

use Illuminate\Database\Schema\Blueprint;
use Moox\Builder\Services\ContextAwareService;
use Moox\Builder\Services\File\FileManager;
use Moox\Builder\Traits\HandlesMigrationFiles;

class MigrationFinder extends ContextAwareService
{
    use HandlesMigrationFiles;

    protected array $migrations = [];

    public function __construct(
        private readonly FileManager $fileManager
    ) {}

    public function execute(): void
    {
        $this->ensureContextIsSet();
        $this->migrations = $this->fileManager->findMigrationFiles(
            $this->context->getPath('migrations')
        );
    }

    public function getMigrations(): array
    {
        return $this->migrations;
    }

    public function findMigrationForTable(string $tableName): ?string
    {
        $pattern = str_replace(
            $this->context->getTableName(),
            $tableName,
            $this->getMigrationPattern()
        );

        $files = glob($pattern);

        return $files ? end($files) : null;
    }

    public function extractBlueprintFromFile(string $path): ?Blueprint
    {
        if (! file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if (preg_match('/Schema::create\([\'"](.+?)[\'"]\s*,\s*function\s*\(Blueprint\s+\$table\)\s*{(.+?)}\);/s', $content, $matches)) {
            return $this->createBlueprintFromContent($matches[1], $matches[2]);
        }

        return null;
    }

    protected function createBlueprintFromContent(string $table, string $content): Blueprint
    {
        $blueprint = new Blueprint($table);
        eval('$blueprint->'.trim($content).';');

        return $blueprint;
    }
}
