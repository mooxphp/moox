<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\Invoice\Support\En16931\Address;
use Moox\Invoice\Support\En16931\Party;

final class PartyAddressLines
{
    /**
     * @return list<string>
     */
    public static function fromParty(Party $party): array
    {
        $name = trim($party->name);

        return array_merge(
            $name !== '' ? [$name] : [],
            self::addressLines($party->address),
        );
    }

    /**
     * @return list<string>
     */
    private static function addressLines(Address $address): array
    {
        return array_values(array_filter([
            ...self::line2Segments($address->line2),
            self::trimmedNonEmpty($address->line1),
            self::trimmedNonEmpty(trim($address->postal_code.' '.$address->city)),
            self::trimmedNonEmpty($address->country_code),
        ]));
    }

    /**
     * @return list<string>
     */
    private static function line2Segments(?string $line2): array
    {
        if ($line2 === null || trim($line2) === '') {
            return [];
        }

        $segments = [];
        foreach (preg_split("/\r\n|\r|\n/", $line2) ?: [] as $segment) {
            $trimmed = self::trimmedNonEmpty((string) $segment);
            if ($trimmed !== null) {
                $segments[] = $trimmed;
            }
        }

        return $segments;
    }

    private static function trimmedNonEmpty(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
