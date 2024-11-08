<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Moox\Builder\Traits\HandlesMigrationFiles;
use RuntimeException;

class PreviewMigrator extends AbstractService
{
    use HandlesMigrationFiles;

    public function execute(): void
    {
        $migrationFile = $this->findMigrationFile();

        if (! $migrationFile) {
            throw new RuntimeException('Migration file not found for table: '.$this->context->getTableName());
        }

        $migration = require $migrationFile;

        if (! is_object($migration) || ! method_exists($migration, 'up')) {
            throw new RuntimeException('Invalid migration file: up() method not found');
        }

        $migration->up();
    }
}
