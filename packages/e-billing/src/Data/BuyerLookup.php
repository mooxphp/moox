<?php

declare(strict_types=1);

namespace Moox\EBilling\Data;

use Moox\EBilling\Contracts\BuyerMasterDataResolver;

/**
 * What the invoice says about its buyer, handed to the {@see BuyerMasterDataResolver}.
 * `reference` is set when a person attributed the document manually.
 */
final readonly class BuyerLookup
{
    public function __construct(
        public ?string $customerNumber,
        public ?string $name,
        public ?string $vatId,
        public ?string $reference = null,
    ) {
    }
}
