<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions\Specifications;

use Moox\Definition\Definitions\Cardinality;
use Moox\Definition\Definitions\EntityReference;

final class MediaSpecification implements Specification
{
    public function __construct(
        private readonly ?EntityReference $target,
        private readonly ?Cardinality $cardinality,
    ) {
    }

    public function target(): ?EntityReference
    {
        return $this->target;
    }

    public function cardinality(): ?Cardinality
    {
        return $this->cardinality;
    }
}
