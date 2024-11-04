<?php

declare(strict_types=1);

namespace Moox\Builder\Contexts;

use InvalidArgumentException;

class PreviewContext extends AbstractBuildContext
{
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
        // Override for model in preview context
        if ($type === 'model') {
            return app_path('Models/'.$this->getEntityName().'.php');
        }

        return parent::getPath($type);
    }

    public function getNamespace(string $type): string
    {
        // Override for model in preview context
        if ($type === 'model') {
            return 'App\\Models';
        }

        return parent::getNamespace($type);
    }
}
