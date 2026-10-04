<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions\Specifications;

use Moox\Definition\Definitions\ValueType;

final class KeyValueSpecification implements Specification
{
    public function __construct(
        private readonly ?ValueType $keyType,
        private readonly ?ValueType $valueType,
    ) {
    }

    public function keyType(): ?ValueType
    {
        return $this->keyType;
    }

    public function valueType(): ?ValueType
    {
        return $this->valueType;
    }
}
