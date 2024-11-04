<?php

declare(strict_types=1);

namespace Moox\Builder\Contexts;

use InvalidArgumentException;

class AppContext extends AbstractBuildContext
{
    public function isPreview(): bool
    {
        return false;
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
        if (! is_dir($this->getBasePath())) {
            throw new InvalidArgumentException('Invalid base path for app context');
        }

        if ($this->getBaseNamespace() !== 'App') {
            throw new InvalidArgumentException('App context must use App namespace');
        }
    }
}
