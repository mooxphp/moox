<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\Invoice\Support\En16931\Party;

final class PartyAddressFormatter
{
    /**
     * Display order: party name, line2 segments (newline-split), line1, postal_code + city, country_code.
     */
    public static function format(?Party $party): ?string
    {
        if ($party === null) {
            return null;
        }

        $lines = PartyAddressLines::fromParty($party);

        return $lines === [] ? null : implode("\n", $lines);
    }
}
