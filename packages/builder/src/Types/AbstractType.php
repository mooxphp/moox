<?php

declare(strict_types=1);

namespace Moox\Builder\Types;

abstract class AbstractType
{
    protected array $availableFields = [];

    protected string $defaultField;

    protected string $databaseType;

    public function getAvailableFields(): array
    {
        return $this->availableFields;
    }

    public function getDefaultField(): string
    {
        return $this->defaultField;
    }

    public function getDatabaseType(): string
    {
        return $this->databaseType;
    }

    public function canUseField(string $field): bool
    {
        return in_array($field, $this->availableFields);
    }
}
