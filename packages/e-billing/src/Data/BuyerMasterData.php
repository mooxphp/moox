<?php

declare(strict_types=1);

namespace Moox\EBilling\Data;

/**
 * The buyer as the host's master data knows it. `party` is an existing record of any model, attributed to the document
 * as `buyer`; `contact` is its organisation, attributed as `buyer_contact` (ADR 0021).
 * `isReviewable` marks a record that must not corroborate silently, e.g. deleted or inactive.
 * Billing addresses corroborate the buyer address and country; delivery addresses are tried first for a consignee,
 * then the billing addresses.
 */
final readonly class BuyerMasterData
{
    /**
     * @param  list<string>  $alternativeNames  further names the buyer is known by (display or legal name)
     * @param  list<BuyerMasterDataAddress>  $billingAddresses
     * @param  list<BuyerMasterDataAddress>  $deliveryAddresses
     */
    public function __construct(
        public PartyReference $party,
        public ?string $name,
        public array $alternativeNames = [],
        public ?string $vatId = null,
        public ?string $customerNumber = null,
        public bool $isReviewable = false,
        public array $billingAddresses = [],
        public array $deliveryAddresses = [],
        public ?PartyReference $contact = null,
    ) {
    }
}
