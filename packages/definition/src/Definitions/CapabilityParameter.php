<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions;

final class CapabilityParameter
{
    public function __construct(
        private readonly string $name,
        private readonly string $type,
        private readonly bool $required,
    ) {
    }

    public static function fromType(string $name, string $type): self
    {
        $required = ! str_ends_with($type, '?');

        return new self($name, $required ? $type : substr($type, 0, -1), $required);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function required(): bool
    {
        return $this->required;
    }
}
