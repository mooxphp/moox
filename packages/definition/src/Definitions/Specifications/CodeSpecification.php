<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions\Specifications;

use Moox\Definition\Definitions\CodeLanguage;

final class CodeSpecification implements Specification
{
    public function __construct(
        private readonly ?CodeLanguage $language,
    ) {
    }

    public function language(): ?CodeLanguage
    {
        return $this->language;
    }
}
