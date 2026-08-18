<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\Invoice\Support\En16931\Address;
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

        $lines = array_merge(
            self::nameLines($party),
            self::addressLines($party->address),
        );

        if ($lines === []) {
            return null;
        }

        return implode("\n", $lines);
    }

    /**
     * @return list<string>
     */
    private static function nameLines(Party $party): array
    {
        $name = trim($party->name);

        return $name !== '' ? [$name] : [];
    }

    /**
     * @return list<string>
     */
    private static function addressLines(Address $address): array
    {
        $lines = self::line2Segments($address->line2);

        $line1 = self::trimmedNonEmpty($address->line1);
        if ($line1 !== null) {
            $lines[] = $line1;
        }

        $postalCity = self::trimmedNonEmpty(trim($address->postal_code.' '.$address->city));
        if ($postalCity !== null) {
            $lines[] = $postalCity;
        }

        $countryCode = self::trimmedNonEmpty($address->country_code);
        if ($countryCode !== null) {
            $lines[] = $countryCode;
        }

        return $lines;
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
