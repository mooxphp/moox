<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions\Specifications;

use Moox\Definition\Definitions\ColorFormat;

final class ColorSpecification implements Specification
{
    public function __construct(
        private readonly ?ColorFormat $format,
    ) {
    }

    public function format(): ?ColorFormat
    {
        return $this->format;
    }
}
