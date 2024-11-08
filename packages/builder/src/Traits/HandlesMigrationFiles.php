<?php

declare(strict_types=1);

namespace Moox\Builder\Traits;

trait HandlesMigrationFiles
{
    protected function getMigrationPattern(): string
    {
        $tableName = $this->context->getTableName();

        return match ($this->context->getContextType()) {
            'app' => base_path('database/migrations/[0-9]{4}_[0-9]{2}_[0-9]{2}_[0-9]{6}_create_'.$tableName.'_table.php'),
            'package' => $this->context->getBasePath().'/database/migrations/create_'.$tableName.'_table.php.stub',
            'preview' => app_path('Builder/database/migrations/preview_[0-9]{4}_[0-9]{2}_[0-9]{2}_[0-9]{6}_create_'.$tableName.'_table.php'),
            default => throw new \InvalidArgumentException('Invalid context type: '.$this->context->getContextType()),
        };
    }

    protected function findMigrationFile(): ?string
    {
        $pattern = $this->getMigrationPattern();
        $files = glob($pattern);

        return $files ? $files[0] : null;
    }
}
