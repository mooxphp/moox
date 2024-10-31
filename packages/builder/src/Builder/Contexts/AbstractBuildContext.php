<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Contexts;

use Illuminate\Support\Str;

abstract class AbstractBuildContext implements BuildContext
{
    public function __construct(
        private readonly string $entityName,
        private readonly string $basePath,
        private readonly string $baseNamespace,
    ) {
        $this->validate();
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getBaseNamespace(): string
    {
        return $this->baseNamespace;
    }

    public function getModelPath(): string
    {
        return $this->getBasePath().'/Models/'.$this->getEntityName().'.php';
    }

    public function getResourcePath(): string
    {
        return $this->getBasePath().'/Resources/'.$this->getEntityName().'Resource.php';
    }

    public function getPluginPath(): string
    {
        return $this->getBasePath().'/Plugins/'.$this->getEntityName().'Plugin.php';
    }

    public function getModelNamespace(): string
    {
        return $this->getBaseNamespace().'\\Models';
    }

    public function getResourceNamespace(): string
    {
        return $this->getBaseNamespace().'\\Resources';
    }

    public function getPluginNamespace(): string
    {
        return $this->getBaseNamespace().'\\Plugins';
    }

    public function getTableName(): string
    {
        return Str::snake(Str::plural($this->getEntityName()));
    }

    public function getPluralModelName(): string
    {
        return Str::plural($this->getEntityName());
    }

    abstract public function validate(): void;
}
