<?php

declare(strict_types=1);

namespace Moox\Invoice\Support\En16931;

/**
 * Normalize legacy delivery JSON between address-shaped and party-shaped storage.
 */
final class DeliveryPartyStoredArray
{
    /**
     * @param  array<string, mixed>|null  $decoded
     * @return array<string, mixed>|null
     */
    public static function migrate(?array $decoded): ?array
    {
        if ($decoded === null) {
            return null;
        }

        if (self::isLegacyAddressShape($decoded)) {
            return self::wrapLegacyAddressShape($decoded);
        }

        return self::stripForbiddenPartyKeys($decoded);
    }

    /**
     * Best-effort reverse of {@see migrate}: unwrap party rows whose name is empty.
     *
     * @param  array<string, mixed>|null  $decoded
     * @return array<string, mixed>|null
     */
    public static function unwrap(?array $decoded): ?array
    {
        if ($decoded === null) {
            return null;
        }

        if (! isset($decoded['address']) || ! is_array($decoded['address'])) {
            return $decoded;
        }

        $name = trim((string) ($decoded['name'] ?? ''));
        if ($name !== '') {
            return $decoded;
        }

        return $decoded['address'];
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    public static function isLegacyAddressShape(array $decoded): bool
    {
        return array_key_exists('country_code', $decoded)
            && ! array_key_exists('address', $decoded);
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return array{name: string, address: array<string, mixed>}
     */
    public static function wrapLegacyAddressShape(array $decoded): array
    {
        $name = (string) ($decoded['name'] ?? '');
        $address = $decoded;
        unset($address['name'], $address['vat_id'], $address['tax_number'], $address['contact']);

        return [
            'name' => $name,
            'address' => $address,
        ];
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return array{name: string, address: array<string, mixed>}|array<string, mixed>
     */
    public static function partyPayloadFromStored(array $decoded): array
    {
        if (self::isLegacyAddressShape($decoded)) {
            return self::wrapLegacyAddressShape($decoded);
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return array<string, mixed>
     */
    private static function stripForbiddenPartyKeys(array $decoded): array
    {
        $forbiddenKeys = ['vat_id', 'tax_number', 'contact'];
        [$cleaned, $dirty] = self::unsetKeysIfPresent($decoded, $forbiddenKeys);

        if (! isset($cleaned['address']) || ! is_array($cleaned['address'])) {
            return $dirty ? $cleaned : $decoded;
        }

        [$address, $addressDirty] = self::unsetKeysIfPresent($cleaned['address'], $forbiddenKeys);
        if ($addressDirty) {
            $cleaned['address'] = $address;
            $dirty = true;
        }

        return $dirty ? $cleaned : $decoded;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $keys
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private static function unsetKeysIfPresent(array $data, array $keys): array
    {
        $dirty = false;

        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                unset($data[$key]);
                $dirty = true;
            }
        }

        return [$data, $dirty];
    }
}
