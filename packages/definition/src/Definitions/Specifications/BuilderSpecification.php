<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions\Specifications;

use Moox\Definition\Definitions\BlockDefinition;

final class BuilderSpecification implements Specification
{
    /**
     * @param  list<BlockDefinition>|null  $blocks
     */
    public function __construct(
        private readonly ?array $blocks,
    ) {
    }

    /**
     * @return list<BlockDefinition>|null
     */
    public function blocks(): ?array
    {
        return $this->blocks;
    }
}
