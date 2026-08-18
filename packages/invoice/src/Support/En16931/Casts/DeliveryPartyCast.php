<?php

declare(strict_types=1);

namespace Moox\Invoice\Support\En16931\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Moox\Invoice\Support\En16931\Address;
use Moox\Invoice\Support\En16931\DeliveryPartyStoredArray;
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
        return DeliveryPartyStoredArray::migrate($decoded);
    }

    /**
     * @param  array<string, mixed>|null  $decoded
     * @return array<string, mixed>|null
     */
    public static function unwrapStoredArray(?array $decoded): ?array
    {
        return DeliveryPartyStoredArray::unwrap($decoded);
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    public static function isLegacyAddressShape(array $decoded): bool
    {
        return DeliveryPartyStoredArray::isLegacyAddressShape($decoded);
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private function partyFromStoredArray(array $decoded): ?Party
    {
        $payload = DeliveryPartyStoredArray::partyPayloadFromStored($decoded);
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

        return json_encode($this->persistedArray($party->name, $party->address), JSON_THROW_ON_ERROR);
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
