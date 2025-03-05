<?php

declare(strict_types=1);

namespace Moox\Builder\Contexts;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;

class BuildContext
{
    public const PREVIEW_PREFIX = 'preview_';

    protected string $pluralName;

    protected ?Command $command = null;

    public function __construct(
        protected string $contextType,
        protected array $config,
        protected array $blocks = [],
        protected string $entityName = '',
        ?string $pluralName = null,
        protected ?string $preset = null
    ) {
        $this->pluralName = $pluralName ?? str($this->entityName)->plural()->toString();
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
        $path = $this->config['generators'][$type]['path'] ?? '';

        if (empty($path)) {
            throw new RuntimeException(sprintf('Path configuration for %s not found', $type));
        }

        return str_replace(
            ['%BasePath%', '%locale%', '\\'],
            [$basePath, config('app.locale', 'en'), '/'],
            $path
        );
    }

    public function getNamespace(string $type): string
    {
        $baseNamespace = $this->getBaseNamespace();
        $namespace = $this->config['generators'][$type]['namespace'] ?? '';

        if (empty($namespace)) {
            throw new RuntimeException(sprintf('Namespace configuration for %s not found', $type));
        }

        return str_replace(
            '%BaseNamespace%',
            $baseNamespace,
            $namespace
        );
    }

    public function getTemplate(string $type): string
    {
        $template = $this->config['generators'][$type]['template'] ?? null;

        if (! $template) {
            throw new RuntimeException(sprintf('Template configuration for %s not found in config', $type));
        }

        return $template;
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
        $tableName = Str::snake($this->entityName);
        $tableName = Str::plural($tableName);

        if ($this->contextType === 'preview') {
            return self::PREVIEW_PREFIX.$tableName;
        }

        return $tableName;
    }

    public function isPackage(): bool
    {
        return $this->contextType === 'package';
    }

    public function getPreset(): ?string
    {
        return $this->preset;
    }

    public function setPreset(string $preset): self
    {
        $this->preset = $preset;

        return $this;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function formatNamespace(string $type, bool $leadingSlash = true): string
    {
        $namespace = $this->getNamespace($type);

        return $leadingSlash ? '\\'.$namespace : $namespace;
    }

    public function getFullyQualifiedName(string $type, string $className): string
    {
        return $this->formatNamespace($type).$className;
    }
}
