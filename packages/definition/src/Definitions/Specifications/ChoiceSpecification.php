<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions\Specifications;

use Moox\Definition\Definitions\ChoiceOptions;
use Moox\Definition\Definitions\ValueType;

final class ChoiceSpecification implements Specification
{
    public function __construct(
        private readonly ?ValueType $valueType,
        private readonly ?ChoiceOptions $options,
        private readonly ?bool $multiple = null,
    ) {
    }

    public function valueType(): ?ValueType
    {
        return $this->valueType;
    }

    public function options(): ?ChoiceOptions
    {
        return $this->options;
    }

    public function multiple(): ?bool
    {
        return $this->multiple;
    }
}
