<?php

declare(strict_types=1);

namespace Moox\EBilling\Contracts;

use Moox\EBilling\Data\BuyerLookup;
use Moox\EBilling\Data\BuyerMasterDataResult;
use Moox\EBilling\Enums\BuyerMasterDataStatus;

/**
 * Looks the buyer of an invoice up in the host's master data: own models, an external API or nothing at all
 * (ADR 0020). The validator only corroborates what is returned; generation never depends on it.
 *
 * Bind an implementation via `e-billing.master_data.resolver`. Exceptions are caught at the seam and treated as
 * {@see BuyerMasterDataStatus::Unavailable}.
 */
interface BuyerMasterDataResolver
{
    /**
     * With a {@see BuyerLookup::$reference} (a manual attribution) resolve exactly that record; otherwise find the
     * buyer by customer number first, then by name.
     */
    public function resolve(BuyerLookup $lookup): BuyerMasterDataResult;
}
