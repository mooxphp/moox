<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions\Specifications;

final class MoneySpecification implements Specification
{
    public function __construct(
        private readonly ?string $currency,
        private readonly ?int $precision,
        private readonly ?int $scale,
    ) {
    }

    public function currency(): ?string
    {
        return $this->currency;
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
