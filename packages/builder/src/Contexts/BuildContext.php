<?php

declare(strict_types=1);

namespace Moox\Builder\Contexts;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class BuildContext
{
    protected string $presetName = 'simple-item';

    protected ?Command $command = null;

    public function __construct(
        protected readonly string $contextType,
        protected readonly string $entityName,
        protected ?string $packageNamespace = null
    ) {
        $this->validate();
    }

    public function getConfig(): array
    {
        return config("builder.contexts.{$this->contextType}");
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function getBasePath(): string
    {
        return $this->getConfig()['base_path'];
    }

    public function getClassPath(): string
    {
        return $this->getConfig()['class_path'];
    }

    public function getBaseNamespace(): string
    {
        return $this->getConfig()['base_namespace'];
    }

    public function getPath(string $type): string
    {
        $config = $this->getConfig()['classes'][$type] ?? null;
        if (! $config) {
            throw new InvalidArgumentException("Unknown class type: {$type}");
        }

        return $this->resolvePath($config['path']).'/'.$this->getFileName($type);
    }

    public function getNamespace(string $type): string
    {
        $config = $this->getConfig()['classes'][$type] ?? null;
        if (! $config) {
            throw new InvalidArgumentException("Unknown class type: {$type}");
        }

        return $this->resolveNamespace($config['namespace']);
    }

    public function getTemplate(string $type): string
    {
        $config = $this->getConfig()['classes'][$type] ?? null;
        if (! $config || ! isset($config['template'])) {
            throw new InvalidArgumentException("No template found for type: {$type}");
        }

        return $config['template'];
    }

    public function getGenerator(string $type): string
    {
        $config = $this->getConfig()['classes'][$type] ?? null;
        if (! $config || ! isset($config['generator'])) {
            throw new InvalidArgumentException("No generator found for type: {$type}");
        }

        return $config['generator'];
    }

    protected function resolvePath(string $path): string
    {
        return str_replace(
            ['%BasePath%', '%ClassPath%'],
            [$this->getBasePath(), $this->getClassPath()],
            $path
        );
    }

    protected function resolveNamespace(string $namespace): string
    {
        return str_replace(
            ['%BaseNamespace%', '%ClassPath%'],
            [$this->getBaseNamespace(), str_replace('/', '\\', $this->getClassPath())],
            $namespace
        );
    }

    protected function getFileName(string $type): string
    {
        return match ($type) {
            'migration' => date('Y_m_d_His').'_create_'.Str::snake(Str::plural($this->entityName)).'_table.php',
            'migration_stub' => 'create_'.Str::snake(Str::plural($this->entityName)).'_table.php.stub',
            default => $this->entityName.Str::studly($type).'.php'
        };
    }

    public function validate(): void
    {
        if (! config("builder.contexts.{$this->contextType}")) {
            throw new InvalidArgumentException("Invalid context type: {$this->contextType}");
        }
    }

    // Preset and Command methods
    public function getPresetName(): string
    {
        return $this->presetName;
    }

    public function setPresetName(string $name): void
    {
        $this->presetName = $name;
    }

    public function getCommand(): ?Command
    {
        return $this->command;
    }

    public function setCommand(Command $command): void
    {
        $this->command = $command;
    }

    public function getTableName(): string
    {
        return strtolower($this->getPluralModelName());
    }

    public function getPluralModelName(): string
    {
        return str_ends_with($this->entityName, 'y')
            ? substr($this->entityName, 0, -1).'ies'
            : $this->entityName.'s';
    }

    public function isPackage(): bool
    {
        return $this->contextType === 'package';
    }

    public function isPreview(): bool
    {
        return $this->contextType === 'preview';
    }

    public function getPageTemplate(string $type, string $page): string
    {
        $config = $this->getConfig()['classes'][$type] ?? null;
        if (! $config || ! isset($config['page_templates'][$page])) {
            throw new RuntimeException("No template configured for {$type} page: {$page}");
        }

        return $config['page_templates'][$page];
    }
}
