<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions\Specifications;

use Moox\Definition\Definitions\Field;

final class RepeaterSpecification implements Specification
{
    /**
     * @param  array<string, Field>|null  $fields
     */
    public function __construct(
        private readonly ?array $fields,
    ) {
    }

    /**
     * @return array<string, Field>|null
     */
    public function fields(): ?array
    {
        return $this->fields;
    }
}
