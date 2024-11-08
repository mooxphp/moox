<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Support\Facades\File;
use Moox\Builder\Traits\HandlesMigrationFiles;
use RuntimeException;

class EntityFilesRemover extends AbstractService
{
    use HandlesMigrationFiles;

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
        $migrationFile = $this->findMigrationFile();

        if ($migrationFile && file_exists($migrationFile)) {
            File::delete($migrationFile);
            $this->log("Successfully deleted migration: {$migrationFile}");
        } else {
            $this->log('No migration file found for table: '.$this->context->getTableName());
        }
    }

    private function log(string $message): void
    {
        if ($command = $this->context->getCommand()) {
            $command->info($message);
        }
        \Log::info('[EntityFilesRemover] '.$message);
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
