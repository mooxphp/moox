<?php

declare(strict_types=1);

namespace Moox\Builder\Contexts;

use InvalidArgumentException;

class PackageContext extends AbstractBuildContext
{
    public function __construct(
        string $entityName,
        string $basePath,
        string $baseNamespace,
        array $paths = []
    ) {
        $defaultPaths = [
            'model' => 'src/Models',
            'resource' => 'src/Resources',
            'plugin' => 'src/Resources',
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
        return false;
    }

    public function isPackage(): bool
    {
        return true;
    }

    public function shouldPublishMigrations(): bool
    {
        return true;
    }

    public function validate(): void
    {
        if (! is_dir($this->getBasePath())) {
            throw new InvalidArgumentException('Invalid package path');
        }

        if (! str_contains($this->getBaseNamespace(), '\\')) {
            throw new InvalidArgumentException('Package namespace must contain vendor name');
        }
    }
}
