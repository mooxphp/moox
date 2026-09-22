<?php

declare(strict_types=1);

namespace Moox\Zugferd\Support;

use Moox\Zugferd\Contracts\ZugferdAddress;

/**
 * ADR 0020: ship-to equals buyer when case-insensitive trimmed names match
 * and postal fingerprints match (street, street2, postal_code, country).
 * City is intentionally not part of the fingerprint.
 */
final class ShipToPartyEquality
{
    public static function equals(
        ?string $leftName,
        ?ZugferdAddress $leftAddress,
        ?string $rightName,
        ?ZugferdAddress $rightAddress,
    ): bool {
        if (! self::namesEqual($leftName, $rightName)) {
            return false;
        }

        return self::fingerprint($leftAddress) === self::fingerprint($rightAddress);
    }

    private static function namesEqual(?string $left, ?string $right): bool
    {
        $a = self::normalizeName($left);
        $b = self::normalizeName($right);

        if ($a === null || $b === null) {
            return false;
        }

        return $a === $b;
    }

    private static function normalizeName(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $collapsed = preg_replace('/\s+/u', ' ', trim($name)) ?? '';

        if ($collapsed === '') {
            return null;
        }

        return mb_strtolower($collapsed);
    }

    /**
     * @return array{street: ?string, street2: ?string, postal_code: ?string, country_code: ?string}
     */
    private static function fingerprint(?ZugferdAddress $address): array
    {
        if ($address === null) {
            return [
                'street' => null,
                'street2' => null,
                'postal_code' => null,
                'country_code' => null,
            ];
        }

        $country = self::normalizeToken($address->country);

        return [
            'street' => self::normalizeToken($address->street),
            'street2' => self::normalizeToken($address->addressLine2),
            'postal_code' => self::normalizeToken($address->zip),
            'country_code' => $country !== null ? strtoupper($country) : null,
        ];
    }

    private static function normalizeToken(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
