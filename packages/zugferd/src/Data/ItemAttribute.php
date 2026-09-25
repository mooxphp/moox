<?php

declare(strict_types=1);

namespace Moox\Zugferd\Data;

use Moox\Zugferd\Contracts\ZugferdItemAttribute;

final class ItemAttribute implements ZugferdItemAttribute
{
    public function __construct(
        public string $name,
        public string $value,
    ) {
    }
}
