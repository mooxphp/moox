<?php

declare(strict_types=1);

namespace Moox\Zugferd\Data;

use Moox\Zugferd\Contracts\ZugferdAddress;

/**
 * Simple postal address for converter tests and internal equality helpers.
 */
final class PostalAddress implements ZugferdAddress
{
    public function __construct(
        public ?string $street = null,
        public ?string $addressLine2 = null,
        public ?string $addressLine3 = null,
        public ?string $zip = null,
        public ?string $city = null,
        public ?string $country = null,
    ) {}
}
