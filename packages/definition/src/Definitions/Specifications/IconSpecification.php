<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions\Specifications;

final class IconSpecification implements Specification
{
    public function __construct(
        private readonly ?string $provider,
        private readonly ?string $set,
    ) {
    }

    public function provider(): ?string
    {
        return $this->provider;
    }

    public function set(): ?string
    {
        return $this->set;
    }
}
