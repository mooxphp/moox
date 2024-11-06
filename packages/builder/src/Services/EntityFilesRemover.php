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

        // Always output, even without command
        $this->log("Searching for migrations with pattern: {$pattern}");

        if (! File::exists(dirname($pattern))) {
            $this->log('Migration directory does not exist: '.dirname($pattern));

            return;
        }

        $files = glob($pattern);
        if ($files === false) {
            $this->log('Error while searching for migration files');

            return;
        }

        if (empty($files)) {
            $this->log('No migration files found. Table name: '.$this->context->getTableName());

            return;
        }

        foreach ($files as $file) {
            if (File::exists($file)) {
                try {
                    File::delete($file);
                    $this->log("Successfully deleted migration: {$file}");
                } catch (\Exception $e) {
                    $this->log("Failed to delete migration {$file}: ".$e->getMessage());
                }
            } else {
                $this->log("Migration file not found: {$file}");
            }
        }
    }

    private function log(string $message): void
    {
        if ($command = $this->context->getCommand()) {
            $command->info($message);
        }
        \Log::info('[EntityFilesRemover] '.$message);
    }

    protected function getMigrationPattern(): string
    {
        $tableName = $this->context->getTableName();
        $this->log("Table name for migration search: {$tableName}");

        if ($this->context->isPackage()) {
            $pattern = $this->context->getBasePath().'/database/migrations/[0-9]*_create_'.$tableName.'_table.php';
        } elseif ($this->context->isPreview()) {
            $pattern = app_path('Builder/database/migrations/[0-9]*_create_'.$tableName.'_table.php');
        } else {
            // App context
            $pattern = base_path('database/migrations/[0-9]*_create_'.$tableName.'_table.php');
        }

        $this->log("Using migration pattern: {$pattern}");

        // Debug: List all files in migrations directory
        $allFiles = glob(dirname($pattern).'/*');
        $this->log('All files in migration directory:');
        foreach ($allFiles as $file) {
            $this->log('- '.basename($file));
        }

        // Debug: Test if specific file exists
        $specificFile = base_path('database/migrations/2024_11_04_194725_create_publishable_items_table.php');
        $this->log('Testing specific file exists: '.$specificFile.' - '.(File::exists($specificFile) ? 'YES' : 'NO'));

        return $pattern;
    }

    protected function getFilePaths(): array
    {
        $paths = [];
        $entityName = $this->context->getEntityName();
        $resourceName = $entityName.'Resource';

        // Model
        $modelPath = $this->normalizePath($this->context->getPath('model').'/'.$entityName.'.php');
        $paths['model'] = $modelPath;

        // Resource
        $resourcePath = $this->normalizePath($this->context->getPath('resource').'/'.$resourceName.'.php');
        $paths['resource'] = $resourcePath;

        // Plugin
        $pluginPath = $this->normalizePath($this->context->getPath('plugin').'/'.$entityName.'Plugin.php');
        $paths['plugin'] = $pluginPath;

        // Resource pages
        $pagesPath = dirname($resourcePath).'/'.$resourceName.'/Pages';
        if (File::exists($pagesPath)) {
            $paths['list_page'] = $this->normalizePath($pagesPath.'/List'.$this->context->getPluralModelName().'.php');
            $paths['create_page'] = $this->normalizePath($pagesPath.'/Create'.$entityName.'.php');
            $paths['edit_page'] = $this->normalizePath($pagesPath.'/Edit'.$entityName.'.php');
            $paths['view_page'] = $this->normalizePath($pagesPath.'/View'.$entityName.'.php');
        }

        // Debug logging
        if ($command = $this->context->getCommand()) {
            $command->info('Files to be removed:');
            foreach ($paths as $type => $path) {
                $command->info("- [$type] $path".(File::exists($path) ? ' (exists)' : ' (not found)'));
            }
        }

        return array_filter($paths, function ($path) {
            return File::exists($path);
        });
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
