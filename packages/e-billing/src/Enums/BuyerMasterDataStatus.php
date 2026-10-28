<?php

declare(strict_types=1);

namespace Moox\EBilling\Enums;

enum BuyerMasterDataStatus: string
{
    case Matched = 'matched';
    case NoMatch = 'no_match';

    /**
     * Several records fit; treated like {@see self::NoMatch} for now.
     */
    case Ambiguous = 'ambiguous';

    /**
     * No master data could be consulted: none bound, or the resolver failed.
     */
    case Unavailable = 'unavailable';
}
