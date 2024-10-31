<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class EntityFilesRemover extends AbstractService
{
    public function execute(): void
    {
        $this->removeFiles();
        if ($this->context->isPreview()) {
            $this->dropTable();
        }
    }

    private function removeFiles(): void
    {
        $paths = [
            $this->context->getModelPath(),
            $this->context->getResourcePath(),
            $this->context->getPluginPath(),
        ];

        $migrationPattern = database_path('migrations/*_create_'.$this->context->getTableName().'_table.php');
        $migrationFiles = glob($migrationPattern);
        $paths = array_merge($paths, $migrationFiles);

        $resourceBasePath = dirname($this->context->getResourcePath()).'/Pages';
        if (File::exists($resourceBasePath)) {
            $paths[] = $resourceBasePath.'/List'.$this->context->getPluralModelName().'.php';
            $paths[] = $resourceBasePath.'/Create'.$this->context->getEntityName().'.php';
            $paths[] = $resourceBasePath.'/Edit'.$this->context->getEntityName().'.php';
            $paths[] = $resourceBasePath.'/View'.$this->context->getEntityName().'.php';
        }

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

    private function dropTable(): void
    {
        Schema::dropIfExists($this->context->getTableName());
    }
}
