<?php

declare(strict_types=1);

namespace Moox\Builder\Contexts;

use InvalidArgumentException;

class PackageContext extends AbstractBuildContext
{
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
