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
    /** @var list<string> */
    private const FORBIDDEN_KEYS = ['vat_id', 'tax_number', 'contact'];

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
            return $this->encodeParty($this->deliveryParty($value->name, $value->address));
        }

        if (is_array($value)) {
            return $this->encodeParty($this->partyFromStoredArray($value));
        }

        return null;
    }

    /**
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
        $payload = self::isLegacyAddressShape($decoded)
            ? self::wrapLegacyAddressShape($decoded)
            : $decoded;
        $name = (string) ($payload['name'] ?? '');
        $addressPayload = $payload['address'] ?? null;

        if (! is_array($addressPayload)) {
            if (trim($name) === '') {
                return null;
            }

            $addressPayload = [];
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

    private function encodeParty(?Party $party): ?string
    {
        if ($party === null) {
            return null;
        }

        return json_encode([
            'name' => $party->name,
            'address' => $party->address->toArray(),
        ], JSON_THROW_ON_ERROR);
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
        [$cleaned, $dirty] = self::unsetKeysIfPresent($decoded, self::FORBIDDEN_KEYS);

        if (! isset($cleaned['address']) || ! is_array($cleaned['address'])) {
            return $dirty ? $cleaned : $decoded;
        }

        [$address, $addressDirty] = self::unsetKeysIfPresent($cleaned['address'], self::FORBIDDEN_KEYS);
        if (! $addressDirty) {
            return $dirty ? $cleaned : $decoded;
        }

        $cleaned['address'] = $address;

        return $cleaned;
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
            if (! array_key_exists($key, $data)) {
                continue;
            }

            unset($data[$key]);
            $dirty = true;
        }

        return [$data, $dirty];
    }
}
