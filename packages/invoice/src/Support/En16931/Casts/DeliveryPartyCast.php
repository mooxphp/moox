<?php

declare(strict_types=1);

namespace Moox\Invoice\Support\En16931\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Moox\Invoice\Support\En16931\Address;
use Moox\Invoice\Support\En16931\Party;

/**
 * Persist delivery as a party (name + address) without VAT, tax number, or contact.
 *
 * @implements CastsAttributes<Party|null, array<string, mixed>|Party|null>
 */
class DeliveryPartyCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Party
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Party) {
            return $this->deliveryParty($value->name, $value->address);
        }

        $decoded = is_string($value) ? json_decode($value, true) : $value;

        if (! is_array($decoded)) {
            return null;
        }

        return $this->partyFromStoredArray($decoded);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Party) {
            $party = $this->deliveryParty($value->name, $value->address);

            if ($party === null) {
                return null;
            }

            return json_encode($this->persistedArray($party->name, $party->address), JSON_THROW_ON_ERROR);
        }

        if (is_array($value)) {
            $party = $this->partyFromStoredArray($value);

            if ($party === null) {
                return null;
            }

            return json_encode($this->persistedArray($party->name, $party->address), JSON_THROW_ON_ERROR);
        }

        return null;
    }

    /**
     * Wrap address-shaped JSON into party shape and strip VAT / tax / contact from party-shaped rows.
     *
     * @param  array<string, mixed>|null  $decoded
     * @return array<string, mixed>|null
     */
    public static function migrateStoredArray(?array $decoded): ?array
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
     * @param  array<string, mixed>  $decoded
     * @return array{name: string, address: array<string, mixed>}
     */
    private static function wrapLegacyAddressShape(array $decoded): array
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

    /**
     * Best-effort reverse of {@see migrateStoredArray}: unwrap party rows whose name is empty.
     *
     * @param  array<string, mixed>|null  $decoded
     * @return array<string, mixed>|null
     */
    public static function unwrapStoredArray(?array $decoded): ?array
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
     */
    private function partyFromStoredArray(array $decoded): ?Party
    {
        if (self::isLegacyAddressShape($decoded)) {
            $name = (string) ($decoded['name'] ?? '');
            $addressPayload = $decoded;
            unset(
                $addressPayload['name'],
                $addressPayload['vat_id'],
                $addressPayload['tax_number'],
                $addressPayload['contact'],
            );
        } else {
            $name = (string) ($decoded['name'] ?? '');
            $addressPayload = $decoded['address'] ?? null;

            if (! is_array($addressPayload)) {
                if (trim($name) === '') {
                    return null;
                }

                $addressPayload = [];
            }
        }

        return $this->deliveryParty($name, Address::fromDocumentArray($addressPayload));
    }

    private function deliveryParty(string $name, Address $address): ?Party
    {
        $trimmedName = trim($name);

        if ($trimmedName === '' && $address->isEmpty()) {
            return null;
        }

        return new Party(
            name: $trimmedName,
            vat_id: null,
            tax_number: null,
            address: $address,
            contact: null,
        );
    }

    /**
     * @return array{name: string, address: array<string, mixed>}
     */
    private function persistedArray(string $name, Address $address): array
    {
        return [
            'name' => $name,
            'address' => $address->toArray(),
        ];
    }
}
