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
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $countryCode = trim((string) ($data['country_code'] ?? ''));

        if ($countryCode === '') {
            throw new IncompleteInvoiceDataException('EN 16931 address requires country_code.');
        }

        return new self(
            line1: (string) ($data['line1'] ?? ''),
            line2: isset($data['line2']) ? (string) $data['line2'] : null,
            city: (string) ($data['city'] ?? ''),
            postal_code: (string) ($data['postal_code'] ?? ''),
            subdivision: isset($data['subdivision']) ? (string) $data['subdivision'] : null,
            country_code: $countryCode,
        );
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
