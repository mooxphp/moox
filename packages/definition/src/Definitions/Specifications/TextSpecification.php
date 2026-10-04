<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions\Specifications;

final class TextSpecification implements Specification
{
    public function __construct(
        private readonly ?int $maxLength,
    ) {
    }

    public function maxLength(): ?int
    {
        return $this->maxLength;
    }
}
