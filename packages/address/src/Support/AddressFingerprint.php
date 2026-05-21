<?php

declare(strict_types=1);

namespace Moox\Address\Support;

use Moox\Address\Models\Address;

final class AddressFingerprint
{
    /**
     * @return list<string>
     */
    public static function columns(): array
    {
        return [
            'street',
            'street2',
            'postal_code',
            'country_code',
        ];
    }

    /**
     * @return array<string, ?string>
     */
    public static function fromAddress(Address $address): array
    {
        return self::fromArray($address->only(self::columns()));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, ?string>
     */
    public static function fromArray(array $attributes): array
    {
        $fingerprint = [];

        foreach (self::columns() as $column) {
            $value = isset($attributes[$column]) ? (string) $attributes[$column] : null;
            $normalized = self::normalize($value);

            $fingerprint[$column] = $column === 'country_code' && $normalized !== null
                ? strtoupper($normalized)
                : $normalized;
        }

        return $fingerprint;
    }

    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }
}
