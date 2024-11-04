<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Illuminate\Support\Facades\File;
use Moox\Builder\Services\EntityFilesRemover;

class DeleteEntityCommand extends AbstractBuilderCommand
{
    protected $signature = 'builder:delete-entity {name} {--force} {--package=} {--app}';

    protected $description = 'Delete an entity and its files';

    public function handle(): void
    {
        $name = $this->argument('name');
        $force = $this->option('force');

        $previewExists = File::exists(app_path('Builder/Resources/'.$name.'Resource.php'));
        $appExists = File::exists(app_path('Models/'.$name.'.php'));
        $packageExists = false;
        $packagePath = '';

        if ($package = $this->option('package')) {
            $packagePath = base_path("packages/{$package}");
            $packageExists = File::exists($packagePath.'/src/Models/'.$name.'.php');
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

        $choices = [];
        if ($appExists) {
            $choices[] = 'App';
        }
        if ($packageExists) {
            $choices[] = 'Package';
        }

        if (count($choices) === 1) {
            $scope = strtolower($choices[0]);
        } else {
            $scope = $this->choice(
                'Multiple entities found. Which one would you like to delete?',
                array_merge($choices, ['All']),
                'All'
            );
            $scope = strtolower($scope);
        }

        match ($scope) {
            'app' => $this->deleteApp($name),
            'package' => $this->deletePackage($name, $package),
            'all' => $this->deleteAll($name, $package),
            default => $this->error('Invalid scope selected'),
        };
    }

    private function deletePreview(string $name): void
    {
        $context = $this->createContext($name, preview: true);
        (new EntityFilesRemover($context))->execute();
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

    private function deleteAll(string $name, ?string $package): void
    {
        if (File::exists(app_path('Models/'.$name.'.php'))) {
            $this->deleteApp($name);
        }
        if ($package && File::exists(base_path("packages/{$package}/src/Models/{$name}.php"))) {
            $this->deletePackage($name, $package);
        }
    }
}
