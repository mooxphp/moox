<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions\Specifications;

final class JsonSpecification implements Specification
{
    /**
     * @param  array<mixed>|null  $schema
     */
    public function __construct(
        private readonly ?array $schema,
    ) {
    }

    /**
     * @return array<mixed>|null
     */
    public function schema(): ?array
    {
        return $this->schema;
    }
}
