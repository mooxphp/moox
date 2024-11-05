<?php

declare(strict_types=1);

namespace Moox\Builder\Contexts;

use InvalidArgumentException;

class PreviewContext extends AbstractBuildContext
{
    public function __construct(
        string $entityName,
        string $basePath,
        string $baseNamespace,
        array $paths = []
    ) {
        $defaultPaths = [
            'model' => 'Builder/Models',
            'resource' => 'Builder/Resources',
            'plugin' => 'Builder/Plugins',
            'migration' => 'database/migrations',
        ];

        parent::__construct(
            entityName: $entityName,
            basePath: $basePath,
            baseNamespace: $baseNamespace,
            paths: array_merge($defaultPaths, $paths)
        );
    }

    public function isPreview(): bool
    {
        return true;
    }

    public function isPackage(): bool
    {
        return false;
    }

    public function shouldPublishMigrations(): bool
    {
        return false;
    }

    public function validate(): void
    {
        if (! is_dir($this->getBasePath()) && ! mkdir($this->getBasePath(), 0755, true)) {
            throw new InvalidArgumentException('Cannot create Builder directory');
        }

        if ($this->getBaseNamespace() !== 'App\\Builder') {
            throw new InvalidArgumentException('Preview context must use App\\Builder namespace');
        }
    }

    public function getPath(string $type): string
    {
        $path = parent::getPath($type);

        // Ensure resource files are in the Resources directory
        if ($type === 'resource') {
            return str_replace('/Resources/Resources/', '/Resources/', $path);
        }

        return $path;
    }

    public function getNamespace(string $type): string
    {
        return match ($type) {
            'model' => 'App\\Builder\\Models',
            'resource' => 'App\\Builder\\Resources',
            'plugin' => 'App\\Builder\\Plugins',
            default => parent::getNamespace($type)
        };
    }
}
