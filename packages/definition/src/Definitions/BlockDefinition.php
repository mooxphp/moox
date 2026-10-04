<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions;

final class BlockDefinition
{
    /**
     * @param  array<string, Field>  $fields
     */
    public function __construct(
        private readonly string $name,
        private readonly array $fields,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, Field>
     */
    public function fields(): array
    {
        return $this->fields;
    }
}
