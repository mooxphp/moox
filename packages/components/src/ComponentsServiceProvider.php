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
        $componentPath = __DIR__.'/Components';
        $namespace = 'Moox\\Components\\Components';

        $components = [];

        foreach ($this->scanDirectory($componentPath) as $file) {
            $relativePath = str_replace([$componentPath, '.php'], '', $file);
            $className = str_replace('/', '\\', $relativePath);
            $fullClassName = $namespace.$className;

            if (class_exists($fullClassName)) {
                $components[] = $fullClassName;
            }
        }

        $this->loadViewComponentsAs('moox', $components);
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
