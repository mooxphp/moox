<?php

declare(strict_types=1);

namespace Moox\Builder\Traits;

trait HandlesMigrationFiles
{
    protected function getMigrationPattern(): string
    {
        $tableName = $this->context->getTableName();
        $migrationPath = database_path('migrations');

        return match ($this->context->getContextType()) {
            'app' => $migrationPath.'/[0-9]{4}_[0-9]{2}_[0-9]{2}_[0-9]{6}_create_'.$tableName.'_table.php',
            'package' => $migrationPath.'/create_'.$tableName.'_table.php.stub',
            'preview' => $migrationPath.'/preview_[0-9]{4}_[0-9]{2}_[0-9]{2}_[0-9]{6}_create_'.$tableName.'_table.php',
            default => throw new \InvalidArgumentException('Invalid context type: '.$this->context->getContextType()),
        };
    }

    protected function findMigrationFile(): ?string
    {
        $pattern = $this->getMigrationPattern();
        if ($this->context->getCommand()) {
            $this->context->getCommand()->info('Looking for migration with pattern: '.$pattern);
            $this->context->getCommand()->info('Files in migrations directory: '.implode(', ', glob(dirname($pattern).'/*')));
        }

        $files = glob($pattern);

        if (empty($files)) {
            if ($this->context->getCommand()) {
                $this->context->getCommand()->error('No migration files found matching pattern');
            }

            return null;
        }

        if ($this->context->getCommand()) {
            $this->context->getCommand()->info('Found migration file: '.$files[0]);
        }

        return $files[0];
    }
}
