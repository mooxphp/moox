<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions;

final class OptionalValue
{
    private function __construct(
        private readonly bool $present,
        private readonly mixed $value,
    ) {
    }

    public static function absent(): self
    {
        return new self(false, null);
    }

    public static function of(mixed $value): self
    {
        return new self(true, $value);
    }

    public function isPresent(): bool
    {
        return $this->present;
    }

    public function value(): mixed
    {
        if (! $this->present) {
            throw new \LogicException('No value is present.');
        }

        return $this->value;
    }
}
