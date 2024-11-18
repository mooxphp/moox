<?php

declare(strict_types=1);

namespace Moox\Builder\Contexts;

use Illuminate\Support\Str;
use RuntimeException;

class BuildContext
{
    public function __construct(
        private readonly string $context,
        private readonly string $entityName,
        private readonly array $config = []
    ) {}

    public function getContext(): string
    {
        return $this->context;
    }

    public function getContextType(): string
    {
        return $this->context;
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function getPluralModelName(): string
    {
        return Str::plural($this->entityName);
    }

    public function getTableName(): string
    {
        return Str::snake(Str::plural($this->entityName));
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getTemplate(string $type): string
    {
        return $this->config['classes'][$type]['template'] ?? throw new RuntimeException("Template not found for type: {$type}");
    }

    public function getPath(string $type): string
    {
        return $this->config['classes'][$type]['path'] ?? throw new RuntimeException("Path not found for type: {$type}");
    }

    public function getNamespace(string $type): string
    {
        return $this->config['classes'][$type]['namespace'] ?? throw new RuntimeException("Namespace not found for type: {$type}");
    }

    public function getPageTemplate(string $type, string $pageType): string
    {
        return $this->config['classes'][$type]['page_templates'][$pageType] ?? throw new RuntimeException("Page template not found for type: {$type} and page: {$pageType}");
    }

    public function getCommand(): ?object
    {
        return $this->config['command'] ?? null;
    }
}
