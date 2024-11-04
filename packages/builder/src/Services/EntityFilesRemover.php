<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;

class EntityFilesRemover extends AbstractService
{
    public function execute(): void
    {
        $paths = $this->getFilePaths();

        if (empty($paths)) {
            throw new RuntimeException('No files found to remove');
        }

        foreach ($paths as $path) {
            if (File::exists($path)) {
                File::delete($path);
                if ($command = $this->context->getCommand()) {
                    $command->info("Deleted: {$path}");
                }

                $dir = dirname($path);
                if (File::isDirectory($dir) && count(File::files($dir)) === 0) {
                    File::deleteDirectory($dir);
                    if ($command = $this->context->getCommand()) {
                        $command->info("Removed empty directory: {$dir}");
                    }
                }
            }
        }

        $this->removeMigrationFiles();
    }

    protected function removeMigrationFiles(): void
    {
        $pattern = $this->getMigrationPattern();
        $files = glob($pattern) ?: [];

        foreach ($files as $file) {
            File::delete($file);
            if ($command = $this->context->getCommand()) {
                $command->info("Deleted migration: {$file}");
            }
        }
    }

    protected function getMigrationPattern(): string
    {
        $basePath = $this->context->isPackage()
            ? $this->context->getBasePath().'/database/migrations'
            : database_path('migrations');

        return $basePath.'/*_create_'.$this->context->getTableName().'_table.php*';
    }

    protected function getFilePaths(): array
    {
        $paths = [];

        // Model
        $modelPath = $this->context->getPath('model');
        $paths['model'] = $modelPath;

        // Resource
        $resourcePath = $this->context->getPath('resource');
        $paths['resource'] = $resourcePath;

        // Plugin
        $pluginPath = $this->context->getPath('plugin');
        $paths['plugin'] = $pluginPath;

        // Resource pages
        $resourceName = $this->context->getEntityName().'Resource';
        $resourcePagesPath = dirname($resourcePath).'/'.$resourceName.'/Pages';

        if (File::exists($resourcePagesPath)) {
            $paths['list_page'] = $resourcePagesPath.'/List'.$this->context->getPluralModelName().'.php';
            $paths['create_page'] = $resourcePagesPath.'/Create'.$this->context->getEntityName().'.php';
            $paths['edit_page'] = $resourcePagesPath.'/Edit'.$this->context->getEntityName().'.php';
            $paths['view_page'] = $resourcePagesPath.'/View'.$this->context->getEntityName().'.php';
        }

        return array_filter($paths);
    }
}
