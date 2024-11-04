<?php

declare(strict_types=1);

namespace Moox\Builder\Contexts;

use Illuminate\Support\Str;

abstract class AbstractBuildContext implements BuildContext
{
    protected string $presetName = 'simple-item';

    public function __construct(
        private readonly string $entityName,
        private readonly string $basePath,
        private readonly string $baseNamespace,
        private readonly array $paths = [],
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

    public function getPath(string $type): string
    {
        $path = $this->paths[$type] ?? '';

        return $this->getBasePath().'/'.$path.'/'.$this->getFileName($type);
    }

    protected function getFileName(string $type): string
    {
        return match ($type) {
            'model' => $this->getEntityName().'.php',
            'resource' => $this->getEntityName().'Resource.php',
            'migration' => $this->getMigrationFileName(),
            'plugin' => $this->getEntityName().'Plugin.php',
            default => throw new \InvalidArgumentException("Unknown file type: {$type}")
        };
    }

    public function getNamespace(string $type): string
    {
        $path = $this->paths[$type] ?? '';

        return $this->getBaseNamespace().'\\'.str_replace('/', '\\', $path);
    }

    public function getTableName(): string
    {
        return Str::snake(Str::plural($this->getEntityName()));
    }

    public function getPluralModelName(): string
    {
        return Str::plural($this->getEntityName());
    }

    protected function getMigrationFileName(): string
    {
        $prefix = $this->isPackage() ? '' : date('Y_m_d_His').'_';
        $suffix = $this->isPackage() ? '.stub' : '.php';

        return $prefix.'create_'.$this->getTableName().'_table'.$suffix;
    }

    public function getPresetName(): string
    {
        return $this->presetName;
    }

    public function setPresetName(string $name): void
    {
        $this->presetName = $name;
    }
}
