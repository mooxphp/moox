<?php

declare(strict_types=1);

namespace Moox\Builder\Contexts;

use Illuminate\Console\Command;
use RuntimeException;

class BuildContext
{
    protected string $contextType;

    protected array $config;

    protected array $blocks;

    protected string $entityName;

    protected string $pluralName;

    protected ?Command $command = null;

    public function __construct(
        string $contextType,
        array $config,
        array $blocks = [],
        string $entityName = '',
        ?string $pluralName = null
    ) {
        $this->contextType = $contextType;
        $this->config = $config;
        $this->blocks = $blocks;
        $this->entityName = $entityName;
        $this->pluralName = $pluralName ?? str($entityName)->plural()->toString();
    }

    public function getContextType(): string
    {
        return $this->contextType;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function getPluralName(): string
    {
        return $this->pluralName;
    }

    public function getPath(string $type): string
    {
        $basePath = $this->getBasePath();
        $path = $this->config['classes'][$type]['path'] ?? '';

        if (empty($path)) {
            throw new RuntimeException("Path configuration for {$type} not found");
        }

        return str_replace(
            ['%BasePath%', '\\'],
            [$basePath, '/'],
            $path
        );
    }

    public function getNamespace(string $type): string
    {
        $baseNamespace = $this->getBaseNamespace();
        $namespace = $this->config['classes'][$type]['namespace'] ?? '';

        if (empty($namespace)) {
            throw new RuntimeException("Namespace configuration for {$type} not found");
        }

        return str_replace(
            '%BaseNamespace%',
            $baseNamespace,
            $namespace
        );
    }

    public function getTemplate(string $type): array
    {
        $templates = $this->config['classes'][$type]['templates'] ?? null;

        if (! $templates) {
            throw new RuntimeException("Template configuration for {$type} not found");
        }

        return $templates;
    }

    protected function getBasePath(): string
    {
        return match ($this->contextType) {
            'preview' => app_path('Builder'),
            'package' => $this->config['package']['path'] ?? '',
            default => base_path()
        };
    }

    public function getBaseNamespace(): string
    {
        return match ($this->contextType) {
            'preview' => 'App\\Builder',
            'package' => $this->config['package']['namespace'] ?? '',
            default => 'App'
        };
    }

    public function setCommand(Command $command): self
    {
        $this->command = $command;

        return $this;
    }

    public function getCommand(): ?Command
    {
        return $this->command;
    }

    public function getTableName(): string
    {
        return str($this->pluralName)->snake()->toString();
    }

    public function isPackage(): bool
    {
        return $this->contextType === 'package';
    }
}
