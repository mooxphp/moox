<?php

declare(strict_types=1);

namespace Moox\Zugferd\Data;

use Moox\Zugferd\Contracts\ZugferdItemClassification;

final class ItemClassification implements ZugferdItemClassification
{
    public function __construct(
        public string $code,
        public string $schemeId,
        public ?string $schemeVersionId = null,
    ) {
    }
}
