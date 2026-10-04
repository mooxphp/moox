<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions\Specifications;

use Moox\Definition\Definitions\Cardinality;
use Moox\Definition\Definitions\EntityReference;
use Moox\Definition\Definitions\Hierarchy;

final class TaxonomySpecification implements Specification
{
    public function __construct(
        private readonly ?EntityReference $target,
        private readonly ?Cardinality $cardinality,
        private readonly ?Hierarchy $hierarchy,
        private readonly ?bool $customTerms,
        private readonly ?bool $sortable,
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

    public function hierarchy(): ?Hierarchy
    {
        return $this->hierarchy;
    }

    public function customTerms(): ?bool
    {
        return $this->customTerms;
    }

    public function sortable(): ?bool
    {
        return $this->sortable;
    }
}
