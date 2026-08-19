<?php

declare(strict_types=1);

namespace Moox\EBilling\Data;

use Moox\Invoice\Support\En16931\Address as En16931Address;
use Moox\Invoice\Support\En16931\Party;
use Moox\Zugferd\Contracts\ZugferdAddress;

class Address implements ZugferdAddress
{
    public function __construct(
        public ?string $company = null,
        public ?string $street = null,
        public ?string $zip = null,
        public ?string $city = null,
        public ?string $country = null,
        public ?string $addressLine2 = null,
        public ?string $addressLine3 = null,
    ) {
    }

    public function equals(?self $other): bool
    {
        if ($other === null) {
            return false;
        }

        return ($this->company ?? '') === ($other->company ?? '')
            && ($this->street ?? '') === ($other->street ?? '')
            && ($this->zip ?? '') === ($other->zip ?? '')
            && ($this->city ?? '') === ($other->city ?? '')
            && ($this->country ?? '') === ($other->country ?? '')
            && ($this->addressLine2 ?? '') === ($other->addressLine2 ?? '')
            && ($this->addressLine3 ?? '') === ($other->addressLine3 ?? '');
    }

    public function toEn16931DeliveryParty(): ?Party
    {
        $name = $this->company !== null ? trim($this->company) : '';
        $line2 = self::joinedAddressLines($this->addressLine2, $this->addressLine3);
        $address = En16931Address::fromDocumentArray([
            'line1' => trim((string) ($this->street ?? '')),
            'line2' => $line2,
            'city' => trim((string) ($this->city ?? '')),
            'postal_code' => trim((string) ($this->zip ?? '')),
            'subdivision' => null,
            'country_code' => $this->country !== null ? strtoupper(trim($this->country)) : '',
        ]);

        return Party::deliveryConsignee($name, $address);
    }

    private static function joinedAddressLines(?string $line2, ?string $line3): ?string
    {
        $trimmedLine2 = self::trimmedNonEmpty($line2);
        $trimmedLine3 = self::trimmedNonEmpty($line3);

        return match (true) {
            $trimmedLine3 === null => $trimmedLine2,
            $trimmedLine2 === null => $trimmedLine3,
            default => $trimmedLine2."\n".$trimmedLine3,
        };
    }

    private static function trimmedNonEmpty(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    /**
     * @return array{company: ?string, street: ?string, zip: ?string, city: ?string, country: ?string, address_line_2: ?string, address_line_3: ?string}
     */
    public function toArray(): array
    {
        return [
            'company' => $this->company,
            'street' => $this->street,
            'zip' => $this->zip,
            'city' => $this->city,
            'country' => $this->country,
            'address_line_2' => $this->addressLine2,
            'address_line_3' => $this->addressLine3,
        ];
    }

    /**
     * @param  array<string, mixed>|string|null  $value
     */
    public static function fromMixedWithParty(array|string|null $value, string $partyName): ?Address
    {
        if ($value === null) {
            return null;
        }
        $partyTrim = trim($partyName);
        if (is_array($value)) {
            $addr = self::fromMixed($value);
            if ($addr === null) {
                return $partyTrim !== '' ? new Address($partyTrim) : null;
            }

            return new Address(
                $partyTrim !== '' ? $partyTrim : $addr->company,
                $addr->street,
                $addr->zip,
                $addr->city,
                $addr->country,
                $addr->addressLine2,
                $addr->addressLine3,
            );
        }

        return self::fromMultilineStringForParty($value, $partyName);
    }

    public static function fromMultilineStringForParty(?string $text, string $partyName): ?Address
    {
        $partyTrim = trim($partyName);
        if ($text === null || trim($text) === '') {
            return $partyTrim !== '' ? new Address($partyTrim) : null;
        }

        $postal = self::fromMultilineString($text);
        if ($postal === null) {
            return $partyTrim !== '' ? new Address($partyTrim) : null;
        }

        return new Address(
            $partyTrim !== '' ? $partyTrim : $postal->company,
            $postal->street,
            $postal->zip,
            $postal->city,
            $postal->country,
            $postal->addressLine2,
            $postal->addressLine3,
        );
    }

    public static function fromMultilineString(?string $text): ?Address
    {
        if ($text === null || trim($text) === '') {
            return null;
        }
        $lines = preg_split('/\r\n|\n|\r/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($lines)) {
            return null;
        }
        $lines = array_values(array_filter(array_map('trim', $lines), fn (string $l): bool => $l !== ''));
        if ($lines === []) {
            return null;
        }

        return new Address(
            company: $lines[0] ?? null,
            street: count($lines) > 1 ? implode("\n", array_slice($lines, 1)) : null,
        );
    }

    /**
     * Accepts a newline-separated address string or an array (e.g. from config / API).
     *
     * @param  array<string, mixed>|string|null  $value
     */
    public static function fromMixed(array|string|null $value): ?Address
    {
        if ($value === null) {
            return null;
        }
        if (is_array($value)) {
            $company = isset($value['company']) ? trim((string) $value['company']) : null;
            $street = $value['street'] ?? null;
            if ($street !== null) {
                $street = trim((string) $street);
                if ($street === '') {
                    $street = null;
                }
            }
            $zip = isset($value['zip']) ? trim((string) $value['zip']) : null;
            $city = isset($value['city']) ? trim((string) $value['city']) : null;
            $country = isset($value['country']) ? trim((string) $value['country']) : null;
            $addressLine2 = isset($value['address_line_2']) ? trim((string) $value['address_line_2']) : null;
            $addressLine3 = isset($value['address_line_3']) ? trim((string) $value['address_line_3']) : null;
            $company = $company === '' ? null : $company;
            $zip = $zip === '' ? null : $zip;
            $city = $city === '' ? null : $city;
            $country = $country === '' ? null : $country;
            $addressLine2 = $addressLine2 === '' ? null : $addressLine2;
            $addressLine3 = $addressLine3 === '' ? null : $addressLine3;

            $addr = new Address(
                company: $company,
                street: $street,
                zip: $zip,
                city: $city,
                country: $country,
                addressLine2: $addressLine2,
                addressLine3: $addressLine3,
            );
            if ($addr->company === null && $addr->street === null && $addr->zip === null && $addr->city === null && $addr->country === null && $addr->addressLine2 === null && $addr->addressLine3 === null) {
                return null;
            }

            return $addr;
        }

        return self::fromMultilineString($value);
    }
}
