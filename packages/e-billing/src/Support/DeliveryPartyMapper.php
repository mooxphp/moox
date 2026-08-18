<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\EBilling\Data\Address;
use Moox\Invoice\Support\En16931\Address as En16931Address;
use Moox\Invoice\Support\En16931\Party;

final class DeliveryPartyMapper
{
    public static function fromDto(?Address $dtoAddress): ?Party
    {
        if ($dtoAddress === null) {
            return null;
        }

        $name = $dtoAddress->company !== null ? trim($dtoAddress->company) : '';
        $address = self::mapAddress($dtoAddress);

        if ($name === '' && $address->isEmpty()) {
            return null;
        }

        return new Party(
            name: $name,
            vat_id: null,
            tax_number: null,
            address: $address,
            contact: null,
        );
    }

    private static function mapAddress(Address $address): En16931Address
    {
        $countryCode = $address->country !== null ? strtoupper(trim($address->country)) : '';
        $line2 = self::joinedLines($address->addressLine2, $address->addressLine3);

        return En16931Address::fromDocumentArray([
            'line1' => trim((string) ($address->street ?? '')),
            'line2' => $line2,
            'city' => trim((string) ($address->city ?? '')),
            'postal_code' => trim((string) ($address->zip ?? '')),
            'subdivision' => null,
            'country_code' => $countryCode,
        ]);
    }

    private static function joinedLines(?string $line2, ?string $line3): ?string
    {
        $trimmedLine2 = self::trimmedNonEmpty($line2);
        $trimmedLine3 = self::trimmedNonEmpty($line3);

        if ($trimmedLine3 === null) {
            return $trimmedLine2;
        }

        if ($trimmedLine2 === null) {
            return $trimmedLine3;
        }

        return $trimmedLine2."\n".$trimmedLine3;
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
