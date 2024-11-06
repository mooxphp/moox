<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Illuminate\Support\Facades\File;
use Moox\Builder\Services\EntityFilesRemover;
use Moox\Builder\Services\EntityTablesRemover;

class DeleteEntityCommand extends AbstractBuilderCommand
{
    protected $signature = 'builder:delete-entity {name} {--force} {--package=} {--app}';

    protected $description = 'Delete an entity and its files';

    public function handle(): void
    {
        $name = $this->argument('name');
        $force = $this->option('force');

        $previewContext = $this->createContext($name, preview: true);
        $appContext = $this->createContext($name);

        $previewPath = $this->normalizePath($previewContext->getPath('resource').'/'.$name.'Resource.php');
        $appPath = $this->normalizePath($appContext->getPath('resource').'/'.$name.'Resource.php');

        $this->info('Checking paths:');
        $this->info("Preview path: $previewPath");
        $this->info("App path: $appPath");
        $this->info('App path exists: '.(File::exists($appPath) ? 'YES' : 'NO'));

        $previewExists = File::exists($previewPath);
        $appExists = File::exists($appPath);
        $packageExists = false;
        $packagePath = '';

        if ($package = $this->option('package')) {
            $packageContext = $this->createContext($name, package: $package);
            $packagePath = $this->normalizePath($packageContext->getPath('resource').'/'.$name.'Resource.php');
            $packageExists = File::exists($packagePath);
            $this->info("Package path: $packagePath");
        }

        if (! $previewExists && ! $appExists && ! $packageExists) {
            $this->error("No entity named '{$name}' found in any scope.");

            return;
        }

        if ($previewExists) {
            $this->deletePreview($name);

            return;
        }

        if ($force) {
            if ($appExists) {
                $this->deleteApp($name);
            }
            if ($packageExists) {
                $this->deletePackage($name, $package);
            }

            return;
        }

        if ($appExists && ! $this->confirm("Are you sure you want to delete the app entity '{$name}'?")) {
            return;
        }

        if ($packageExists && ! $this->confirm("Are you sure you want to delete the package entity '{$name}'?")) {
            return;
        }

        if ($appExists) {
            $this->deleteApp($name);
        }
        if ($packageExists) {
            $this->deletePackage($name, $package);
        }
    }

    private function deletePreview(string $name): void
    {
        $context = $this->createContext($name, preview: true);
        (new EntityFilesRemover($context))->execute();
        (new EntityTablesRemover($context))->execute();

        $this->info("Preview entity '{$name}' deleted successfully!");
    }

    private function deleteApp(string $name): void
    {
        $context = $this->createContext($name, preview: false);
        (new EntityFilesRemover($context))->execute();
        $this->info("App entity '{$name}' deleted successfully!");
    }

    private function deletePackage(string $name, string $package): void
    {
        $context = $this->createContext($name, package: $package);
        (new EntityFilesRemover($context))->execute();
        $this->info("Package entity '{$name}' deleted successfully!");
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
