<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions\Specifications;

use Moox\Definition\Definitions\ValueType;

final class SliderSpecification implements Specification
{
    public function __construct(
        private readonly ?ValueType $valueType,
        private readonly int|string|null $min,
        private readonly int|string|null $max,
        private readonly int|string|null $step,
        private readonly ?bool $range,
        private readonly ?int $precision = null,
        private readonly ?int $scale = null,
    ) {
    }

    public function valueType(): ?ValueType
    {
        return $this->valueType;
    }

    public function min(): int|string|null
    {
        return $this->min;
    }

    public function max(): int|string|null
    {
        return $this->max;
    }

    public function step(): int|string|null
    {
        return $this->step;
    }

    public function range(): ?bool
    {
        return $this->range;
    }

    public function precision(): ?int
    {
        return $this->precision;
    }

    public function scale(): ?int
    {
        return $this->scale;
    }
}
