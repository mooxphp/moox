<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\EBilling\Contracts\BuyerMasterDataResolver;
use Moox\EBilling\Data\BuyerLookup;
use Moox\EBilling\Data\BuyerMasterDataResult;

/**
 * Default resolver — e-billing never requires master data, so without a host resolver it is always unavailable.
 */
final class NullBuyerMasterDataResolver implements BuyerMasterDataResolver
{
    public function resolve(BuyerLookup $lookup): BuyerMasterDataResult
    {
        return BuyerMasterDataResult::unavailable();
    }
}
