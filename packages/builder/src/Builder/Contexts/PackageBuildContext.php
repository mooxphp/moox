<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Contexts;

use InvalidArgumentException;

class PackageBuildContext extends AbstractBuildContext
{
    public function isPreview(): bool
    {
        return false;
    }

    public function isPackage(): bool
    {
        return true;
    }

    public function getMigrationPath(): string
    {
        return $this->getBasePath().'/database/migrations/'.$this->getMigrationFileName();
    }

    public function getMigrationFileName(): string
    {
        return 'create_'.$this->getTableName().'_table.php.stub';
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

    public function getModelNamespace(): string
    {
        return $this->getBaseNamespace().'\\Models';
    }

    public function getResourceNamespace(): string
    {
        return $this->getBaseNamespace().'\\Resources';
    }
}
