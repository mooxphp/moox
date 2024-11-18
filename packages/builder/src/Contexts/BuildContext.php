<?php

declare(strict_types=1);

namespace Moox\Builder\Contexts;

use Illuminate\Support\Str;

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

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function getTableName(): string
    {
        return Str::plural(Str::snake($this->entityName));
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
