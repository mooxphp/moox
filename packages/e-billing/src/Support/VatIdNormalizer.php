<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

final class VatIdNormalizer
{
    /** @var list<string> */
    private const EU_COUNTRY_PREFIXES = [
        'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'EL', 'ES', 'FI', 'FR',
        'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO',
        'SE', 'SI', 'SK', 'XI',
    ];

    public static function normalize(?string $vatId): ?string
    {
        if ($vatId === null) {
            return null;
        }

        $normalized = preg_replace('/\s+/', '', trim($vatId));

        if (! is_string($normalized) || $normalized === '') {
            return null;
        }

        return $normalized;
    }

    public static function euCountryPrefix(?string $vatId): ?string
    {
        $normalized = self::normalize($vatId);
        if ($normalized === null || strlen($normalized) < 2) {
            return null;
        }

        $prefix = strtoupper(substr($normalized, 0, 2));

        return preg_match('/^[A-Z]{2}$/', $prefix) === 1 ? $prefix : null;
    }

    public static function isEuCountryPrefix(string $prefix): bool
    {
        return in_array(strtoupper($prefix), self::EU_COUNTRY_PREFIXES, true);
    }

    public static function isIntraCommunitySupply(?string $sellerVatId, ?string $buyerVatId): bool
    {
        $sellerPrefix = self::euCountryPrefix($sellerVatId);
        $buyerPrefix = self::euCountryPrefix($buyerVatId);

        if ($sellerPrefix === null || $buyerPrefix === null) {
            return false;
        }

        return $sellerPrefix !== $buyerPrefix
            && self::isEuCountryPrefix($sellerPrefix)
            && self::isEuCountryPrefix($buyerPrefix);
    }
}
