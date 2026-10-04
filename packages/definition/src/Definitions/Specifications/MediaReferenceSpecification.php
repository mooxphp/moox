<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions\Specifications;

use Moox\Definition\Definitions\EntityReference;

final class MediaReferenceSpecification implements Specification
{
    public function __construct(
        private readonly ?EntityReference $target,
    ) {
    }

    public function target(): ?EntityReference
    {
        return $this->target;
    }
}
