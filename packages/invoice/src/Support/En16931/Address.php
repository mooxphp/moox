<?php

declare(strict_types=1);

namespace Moox\Invoice\Support\En16931;

use Moox\Invoice\Exceptions\IncompleteInvoiceDataException;

readonly class Address
{
    public function __construct(
        public string $line1,
        public ?string $line2,
        public string $city,
        public string $postal_code,
        public ?string $subdivision,
        public string $country_code,
    ) {
    }

    /**
     * Ready-to-emit address. Requires a country code (EN 16931 BR-57 / BT-80 when BG-15 is emitted).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $countryCode = trim((string) ($data['country_code'] ?? ''));

        if ($countryCode === '') {
            throw new IncompleteInvoiceDataException('EN 16931 address requires country_code.');
        }

        return self::fromDocumentArray($data);
    }

    /**
     * Address as read from a document. Empty country_code is allowed; BR-57 is enforced at emission.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromDocumentArray(array $data): self
    {
        return new self(
            line1: (string) ($data['line1'] ?? ''),
            line2: isset($data['line2']) ? (string) $data['line2'] : null,
            city: (string) ($data['city'] ?? ''),
            postal_code: (string) ($data['postal_code'] ?? ''),
            subdivision: isset($data['subdivision']) ? (string) $data['subdivision'] : null,
            country_code: trim((string) ($data['country_code'] ?? '')),
        );
    }

    public function hasCountry(): bool
    {
        return trim($this->country_code) !== '';
    }

    public function isEmpty(): bool
    {
        return trim($this->line1) === ''
            && trim($this->city) === ''
            && trim($this->postal_code) === ''
            && trim($this->country_code) === '';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'line1' => $this->line1,
            'line2' => $this->line2,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'subdivision' => $this->subdivision,
            'country_code' => $this->country_code,
        ];
    }
}
