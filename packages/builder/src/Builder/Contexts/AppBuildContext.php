<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Contexts;

use InvalidArgumentException;

class AppBuildContext extends AbstractBuildContext
{
    public function isPreview(): bool
    {
        return false;
    }

    public function isPackage(): bool
    {
        return false;
    }

    public function getMigrationPath(): string
    {
        return base_path('database/migrations/').$this->getMigrationFileName();
    }

    public function getMigrationFileName(): string
    {
        return date('Y_m_d_His').'_create_'.$this->getTableName().'_table.php';
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

    public function getModelNamespace(): string
    {
        return 'App\\Models';
    }

    public function getResourceNamespace(): string
    {
        return 'App\\Filament\\Resources';
    }
}
