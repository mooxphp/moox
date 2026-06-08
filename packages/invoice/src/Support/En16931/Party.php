<?php

declare(strict_types=1);

namespace Moox\Invoice\Support\En16931;

use Moox\Invoice\Exceptions\IncompleteInvoiceDataException;

readonly class Party
{
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
}
