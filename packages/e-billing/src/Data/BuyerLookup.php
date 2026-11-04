<?php

declare(strict_types=1);

namespace Moox\EBilling\Data;

use Moox\EBilling\Contracts\BuyerMasterDataResolver;

/**
 * What the invoice says about its buyer, handed to the {@see BuyerMasterDataResolver}.
 * `reference` is the attributed buyer when there is one to resolve exactly (a manual attribution, the review options).
 */
final readonly class BuyerLookup
{
    public function __construct(
        public ?string $customerNumber,
        public ?string $name,
        public ?string $vatId,
        public ?PartyReference $reference = null,
    ) {
    }

    public static function byReference(PartyReference $reference): self
    {
        return new self(null, null, null, $reference);
    }
}
