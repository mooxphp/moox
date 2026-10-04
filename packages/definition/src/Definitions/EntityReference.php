<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions;

final class EntityReference
{
    public function __construct(
        private readonly string $package,
        private readonly string $entity,
        private readonly string $field,
    ) {
    }

    public function package(): string
    {
        return $this->package;
    }

    public function entity(): string
    {
        return $this->entity;
    }

    public function field(): string
    {
        return $this->field;
    }

    public function key(): string
    {
        return $this->package.'.'.$this->entity.'.'.$this->field;
    }
}
