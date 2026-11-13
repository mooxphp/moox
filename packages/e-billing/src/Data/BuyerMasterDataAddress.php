<?php

declare(strict_types=1);

namespace Moox\EBilling\Data;

/**
 * One master-data address of the buyer, in the parts of an invoice address. `line2` may hold several lines.
 * `name` is the company or person kept on the address, e.g. a third party the buyer has goods delivered to;
 * null when master data keeps none.
 */
final readonly class BuyerMasterDataAddress
{
    public function __construct(
        public ?string $reference,
        public ?string $line1,
        public ?string $line2 = null,
        public ?string $postalCode = null,
        public ?string $city = null,
        public ?string $subdivision = null,
        public ?string $countryCode = null,
        public ?string $name = null,
    ) {
    }
}
