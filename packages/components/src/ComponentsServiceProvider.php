<?php

declare(strict_types=1);

namespace Moox\Components;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class ComponentsServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('components')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands();
    }

    public function bootingPackage(): void
    {
        $componentPath = __DIR__.DIRECTORY_SEPARATOR.'Components';
        $namespace = 'Moox\\Components\\Components';

        $components = [];

        foreach ($this->scanDirectory($componentPath) as $file) {
            $relativePath = $this->getRelativePath($file, $componentPath);
            $className = $this->convertPathToClassName($relativePath);
            $fullClassName = $namespace.'\\'.$className;

            if (class_exists($fullClassName)) {
                $components[] = $fullClassName;
            }
        }

        $this->loadViewComponentsAs('moox', $components);
    }

    private function getRelativePath(string $file, string $componentPath): string
    {
        // Normalize paths to use forward slashes for consistent comparison
        $file = str_replace('\\', '/', $file);
        $componentPath = str_replace('\\', '/', $componentPath);

        // Remove the component path prefix and .php extension
        $relativePath = str_replace([$componentPath, '.php'], '', $file);

        // Remove leading/trailing slashes
        return trim($relativePath, '/');
    }

    private function convertPathToClassName(string $path): string
    {
        // Convert directory separators to namespace separators
        return str_replace('/', '\\', $path);
    }

    private function scanDirectory(string $path): array
    {
        $files = [];

        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        ) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getRealPath();
            }
        }

        return $files;
    }
}
