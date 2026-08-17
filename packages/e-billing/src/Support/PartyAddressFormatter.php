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

        $lines = [];

        if (trim($party->name) !== '') {
            $lines[] = trim($party->name);
        }

        $address = $party->address;

        if ($address->line2 !== null && trim($address->line2) !== '') {
            foreach (preg_split("/\r\n|\r|\n/", $address->line2) ?: [] as $segment) {
                $segment = trim((string) $segment);
                if ($segment !== '') {
                    $lines[] = $segment;
                }
            }
        }

        if (trim($address->line1) !== '') {
            $lines[] = trim($address->line1);
        }

        $postalCity = trim($address->postal_code.' '.$address->city);
        if ($postalCity !== '') {
            $lines[] = $postalCity;
        }

        if (trim($address->country_code) !== '') {
            $lines[] = trim($address->country_code);
        }

        if ($lines === []) {
            return null;
        }

        return implode("\n", $lines);
    }
}
