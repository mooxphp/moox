<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use RuntimeException;

class PreviewMigrator extends AbstractService
{
    public function execute(): void
    {
        $migrationFile = $this->context->getPath('migration');

        if (! file_exists($migrationFile)) {
            throw new RuntimeException('Migration file not found: '.$migrationFile);
        }

        $migration = include $migrationFile;

        if (! is_object($migration) || ! method_exists($migration, 'up')) {
            throw new RuntimeException('Invalid migration file: up() method not found');
        }

        $migration->up();
    }
}
