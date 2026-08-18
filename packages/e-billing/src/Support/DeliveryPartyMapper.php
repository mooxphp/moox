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
        $line1 = trim((string) ($address->street ?? ''));
        $line2 = AddressLineMerger::join($address->addressLine2, $address->addressLine3);

        return En16931Address::fromDocumentArray([
            'line1' => $line1,
            'line2' => $line2,
            'city' => trim((string) ($address->city ?? '')),
            'postal_code' => trim((string) ($address->zip ?? '')),
            'subdivision' => null,
            'country_code' => $countryCode,
        ]);
    }
}
