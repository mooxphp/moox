<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions\Specifications;

final class DecimalSpecification implements Specification
{
    public function __construct(
        private readonly ?int $precision,
        private readonly ?int $scale,
    ) {
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
