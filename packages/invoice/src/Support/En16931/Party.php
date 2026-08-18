<?php

declare(strict_types=1);

namespace Moox\Invoice\Support\En16931;

use Moox\Invoice\Exceptions\IncompleteInvoiceDataException;

readonly class Party
{
    /** @var list<string> */
    private const DELIVERY_FORBIDDEN_KEYS = ['vat_id', 'tax_number', 'contact'];

    public function __construct(
        public string $name,
        public ?string $vat_id,
        public ?string $tax_number,
        public Address $address,
        public ?Contact $contact,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            throw new IncompleteInvoiceDataException('EN 16931 party requires name.');
        }

        if (! isset($data['address']) || ! is_array($data['address'])) {
            throw new IncompleteInvoiceDataException('EN 16931 party requires address.');
        }

        $contact = null;

        if (isset($data['contact']) && is_array($data['contact'])) {
            $contact = Contact::fromArray($data['contact']);
        }

        return new self(
            name: $name,
            vat_id: isset($data['vat_id']) ? (string) $data['vat_id'] : null,
            tax_number: isset($data['tax_number']) ? (string) $data['tax_number'] : null,
            address: Address::fromArray($data['address']),
            contact: $contact,
        );
    }

    public static function deliveryFromCastValue(mixed $value): ?self
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof self) {
            return self::deliveryConsignee($value->name, $value->address);
        }

        $decoded = is_string($value) ? json_decode($value, true) : $value;

        return is_array($decoded) ? self::fromDeliveryStoredArray($decoded) : null;
    }

    public static function deliveryToJson(mixed $value): ?string
    {
        $party = $value instanceof self
            ? self::deliveryConsignee($value->name, $value->address)
            : (is_array($value) ? self::fromDeliveryStoredArray($value) : null);

        if ($party === null) {
            return null;
        }

        return json_encode([
            'name' => $party->name,
            'address' => $party->address->toArray(),
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>|null  $decoded
     * @return array<string, mixed>|null
     */
    public static function migrateDeliveryStored(?array $decoded): ?array
    {
        if ($decoded === null) {
            return null;
        }

        if (self::isLegacyDeliveryAddressShape($decoded)) {
            return self::wrapLegacyDeliveryAddressShape($decoded);
        }

        return self::stripForbiddenDeliveryKeys($decoded);
    }

    /**
     * @param  array<string, mixed>|null  $decoded
     * @return array<string, mixed>|null
     */
    public static function unwrapDeliveryStored(?array $decoded): ?array
    {
        if ($decoded === null) {
            return null;
        }

        if (! isset($decoded['address']) || ! is_array($decoded['address'])) {
            return $decoded;
        }

        return trim((string) ($decoded['name'] ?? '')) !== ''
            ? $decoded
            : $decoded['address'];
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    public static function isLegacyDeliveryAddressShape(array $decoded): bool
    {
        return array_key_exists('country_code', $decoded)
            && ! array_key_exists('address', $decoded);
    }

    public static function deliveryConsignee(string $name, Address $address): ?self
    {
        $trimmedName = trim($name);

        if ($trimmedName === '' && $address->isEmpty()) {
            return null;
        }

        return new self(
            name: $trimmedName,
            vat_id: null,
            tax_number: null,
            address: $address,
            contact: null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'vat_id' => $this->vat_id,
            'tax_number' => $this->tax_number,
            'address' => $this->address->toArray(),
            'contact' => $this->contact?->toArray(),
        ];
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private static function fromDeliveryStoredArray(array $decoded): ?self
    {
        $payload = self::isLegacyDeliveryAddressShape($decoded)
            ? self::wrapLegacyDeliveryAddressShape($decoded)
            : $decoded;
        $name = (string) ($payload['name'] ?? '');
        $addressPayload = $payload['address'] ?? null;

        if (! is_array($addressPayload)) {
            return trim($name) === '' ? null : self::deliveryConsignee($name, Address::fromDocumentArray([]));
        }

        return self::deliveryConsignee($name, Address::fromDocumentArray($addressPayload));
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return array{name: string, address: array<string, mixed>}
     */
    private static function wrapLegacyDeliveryAddressShape(array $decoded): array
    {
        $name = (string) ($decoded['name'] ?? '');
        $address = array_diff_key($decoded, array_flip(['name', ...self::DELIVERY_FORBIDDEN_KEYS]));

        return ['name' => $name, 'address' => $address];
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return array<string, mixed>
     */
    private static function stripForbiddenDeliveryKeys(array $decoded): array
    {
        $stripped = self::withoutDeliveryForbiddenKeys($decoded);

        return $stripped ?? $decoded;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private static function withoutDeliveryForbiddenKeys(array $data): ?array
    {
        $result = array_diff_key($data, array_flip(self::DELIVERY_FORBIDDEN_KEYS));
        $changed = $result != $data;

        if (! isset($result['address']) || ! is_array($result['address'])) {
            return $changed ? $result : null;
        }

        $address = array_diff_key($result['address'], array_flip(self::DELIVERY_FORBIDDEN_KEYS));
        if ($address == $result['address']) {
            return $changed ? $result : null;
        }

        $result['address'] = $address;

        return $result;
    }
}
