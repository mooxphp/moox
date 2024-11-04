<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Support\Facades\File;

class EntityFilesRemover extends AbstractService
{
    public function execute(): void
    {
        $paths = $this->getFilePaths();

        foreach ($paths as $path) {
            if (File::exists($path)) {
                if (is_dir($path)) {
                    File::deleteDirectory($path);
                } else {
                    File::delete($path);
                }
            }
        }
    }

    protected function getFilePaths(): array
    {
        $paths = [
            $this->context->getPath('model'),
            $this->context->getPath('resource'),
            $this->context->getPath('plugin'),
        ];

        // Add migration files
        $migrationPattern = $this->getMigrationPattern();
        $migrationFiles = glob($migrationPattern);
        $paths = array_merge($paths, $migrationFiles);

        // Add resource pages
        $resourceBasePath = dirname($this->context->getPath('resource')).'/Pages';
        if (File::exists($resourceBasePath)) {
            $paths = array_merge($paths, [
                $resourceBasePath.'/List'.$this->context->getPluralModelName().'.php',
                $resourceBasePath.'/Create'.$this->context->getEntityName().'.php',
                $resourceBasePath.'/Edit'.$this->context->getEntityName().'.php',
                $resourceBasePath.'/View'.$this->context->getEntityName().'.php',
            ]);
        }

        return $paths;
    }

    protected function getMigrationPattern(): string
    {
        if ($this->context->isPackage()) {
            return dirname($this->context->getPath('migration')).'/*_create_'.$this->context->getTableName().'_table.php.stub';
        }

        return database_path('migrations/*_create_'.$this->context->getTableName().'_table.php');
    }
}
