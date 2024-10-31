<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Contexts;

use InvalidArgumentException;

class PreviewBuildContext extends AbstractBuildContext
{
    public function __construct(string $entityName)
    {
        parent::__construct(
            entityName: $entityName,
            basePath: app_path('Builder'),
            baseNamespace: 'App\\Builder'
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
        if (! is_dir($this->getBasePath()) && ! mkdir($this->getBasePath(), 0755, true)) {
            throw new InvalidArgumentException('Cannot create Builder directory');
        }

        if ($this->getBaseNamespace() !== 'App\\Builder') {
            throw new InvalidArgumentException('Preview context must use App\\Builder namespace');
        }
    }

    public function getModelNamespace(): string
    {
        return 'App\\Builder\\Models';
    }

    public function getResourceNamespace(): string
    {
        return 'App\\Builder\\Resources';
    }
}
