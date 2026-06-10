<?php

declare(strict_types=1);

namespace Moox\EBilling\Data;

use Moox\Zugferd\Contracts\ZugferdAddress;

class Address implements ZugferdAddress
{
    public const LEGAL_FORM_SUFFIXES = [
        'GmbH',
        'AG',
        'KG',
        'OHG',
        'e.K.',
        'e.V.',
        'mbH',
        'Co.',
        'Co. KG',
        'GmbH & Co. KG',
        'Ltd.',
        'UG',
        'UG (haftungsbeschränkt)',
        'SE',
        'GbR',
        'KGaA',
        'gGmbH',
        'Stiftung',
    ];

    public const ADDRESS_INFO_MARKERS = [
        'Abteilung',
        'Abt.',
        'Bereich',
        'z.Hd.',
        'z.H.',
        'c/o',
        'i.A.',
        'Postfach',
        'Gebäude',
        'Geb.',
        'Haus',
        'Stockwerk',
        'OG',
        'Etage',
        'Eingang',
        'Niederlassung',
        'Filiale',
        'Werk',
        'Standort',
    ];

    public function __construct(
        public ?string $company = null,
        public ?string $street = null,
        public ?string $zip = null,
        public ?string $city = null,
        public ?string $country = null,
        public ?string $addressLine2 = null,
        public ?string $addressLine3 = null,
    ) {}

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
     * @return array{zip: string, city: string}|null
     */
    private static function extractPostalAndCity(string $line): ?array
    {
        $candidate = trim($line);
        if ($candidate === '') {
            return null;
        }

        if (preg_match('/^(?:(DE-|D-))?(\d{5})\h+(\D.+)$/ui', $candidate, $m) === 1) {
            $city = trim($m[3]);
            if ($city === '') {
                return null;
            }

            return [
                'zip' => $m[2],
                'city' => $city,
            ];
        }

        if (! preg_match('/^(?<prefix>A|AT|BE|CH|CZ|FR|IT|LU|NL|PL)-(?<zip>[A-Z0-9][A-Z0-9\h-]{2,10})\h+(?<city>\D.+)$/ui', $candidate, $m)) {
            return null;
        }

        $city = trim($m['city']);
        if ($city === '') {
            return null;
        }

        return [
            'zip' => trim($m['zip']),
            'city' => $city,
        ];
    }

    /**
     * @param  array<int, string>  $lines
     * @return array{zip: string, city: string}|null
     */
    private static function pullFirstPostalAndCity(array &$lines): ?array
    {
        foreach ($lines as $idx => $line) {
            $postal = self::extractPostalAndCity($line);
            if ($postal === null) {
                continue;
            }

            unset($lines[$idx]);
            $lines = array_values($lines);

            return $postal;
        }

        return null;
    }

    /**
     * @param  array<int, string>  $lines
     */
    private static function streetFromLines(array $lines): ?string
    {
        return self::postalAddressPartsFromLines($lines)['street'];
    }

    /**
     * @param  array<int, string>  $lines
     * @return array{street: ?string, address_line_2: ?string, address_line_3: ?string}
     */
    private static function postalAddressPartsFromLines(array $lines): array
    {
        $streetIdx = self::findStreetLineIndex($lines);
        if ($streetIdx === null) {
            return [
                'street' => $lines !== [] ? implode("\n", $lines) : null,
                'address_line_2' => null,
                'address_line_3' => null,
            ];
        }

        $additionalLines = array_values(array_filter(
            array_slice($lines, 0, $streetIdx),
            fn (string $line): bool => trim($line) !== ''
        ));

        return [
            'street' => $lines[$streetIdx],
            'address_line_2' => $additionalLines[0] ?? null,
            'address_line_3' => $additionalLines[1] ?? null,
        ];
    }

    /**
     * @param  array<int, string>  $lines
     * @return array{company: ?string, street: ?string, address_line_2: ?string, address_line_3: ?string, discarded_address_info_lines: array<int, string>}
     */
    private static function companyAddressPartsFromLines(array $lines): array
    {
        if ($lines === []) {
            return [
                'company' => null,
                'street' => null,
                'address_line_2' => null,
                'address_line_3' => null,
                'discarded_address_info_lines' => [],
            ];
        }

        $companyParts = [];
        $addressInfoLines = [];
        $streetCandidateLines = [];
        $isCollectingAddressInfo = false;
        $companyIsComplete = false;

        foreach (array_values($lines) as $idx => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if ($idx === 0) {
                $companyParts[] = $line;
                $companyIsComplete = self::lineEndsWithLegalFormSuffix($line);

                continue;
            }

            if (self::isStreetLine($line)) {
                $streetCandidateLines[] = $line;

                break;
            }

            if ($isCollectingAddressInfo || self::lineStartsWithAddressInfoMarker($line) || $companyIsComplete) {
                $isCollectingAddressInfo = true;
                $addressInfoLines[] = $line;

                continue;
            }

            $companyParts[] = $line;
            if (self::lineEndsWithLegalFormSuffix($line)) {
                $companyIsComplete = true;
            }
        }

        return [
            'company' => $companyParts !== [] ? implode(' ', $companyParts) : null,
            'street' => self::streetFromLines($streetCandidateLines),
            'address_line_2' => $addressInfoLines[0] ?? null,
            'address_line_3' => $addressInfoLines[1] ?? null,
            'discarded_address_info_lines' => array_slice($addressInfoLines, 2),
        ];
    }

    private static function lineStartsWithAddressInfoMarker(string $line): bool
    {
        foreach (self::ADDRESS_INFO_MARKERS as $marker) {
            if (preg_match('/^'.preg_quote($marker, '/').'(?:\s|$|[:.,-])/ui', $line) === 1) {
                return true;
            }
        }

        return false;
    }

    private static function lineEndsWithLegalFormSuffix(string $line): bool
    {
        foreach (self::LEGAL_FORM_SUFFIXES as $suffix) {
            if (preg_match('/(?:^|\s)'.preg_quote($suffix, '/').'\.?$/ui', trim($line)) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $lines
     */
    private static function findStreetLineIndex(array $lines): ?int
    {
        foreach ($lines as $idx => $line) {
            if (self::isStreetLine($line)) {
                return $idx;
            }
        }

        return null;
    }

    private static function isStreetLine(string $line): bool
    {
        $candidate = trim($line);
        if ($candidate === '' || self::extractPostalAndCity($candidate) !== null) {
            return false;
        }
        if (preg_match('/^Postfach\b/ui', $candidate) === 1) {
            return false;
        }
        if (preg_match('/\b[\p{L}.-]*(?:straße|strasse|str\.|weg|allee|platz|gasse|ring|damm|ufer|chaussee|markt|pfad|steig)\.?\s*\d*\p{L}*(?:\s*[-\/]\s*\d*\p{L}*)?$/ui', $candidate) === 1) {
            return true;
        }

        return preg_match('/\p{L}/u', $candidate) === 1
            && preg_match('/\b\d+\s*[A-Z]?(?:[-\/]\s*\d+\s*[A-Z]?)?$/ui', $candidate) === 1;
    }

    /**
     * Postal lines only: last line may be "12345 City", "D-12345 City", or "DE-12345 City"; preceding lines become street. Company is always null.
     *
     * @param  array<int, string>  $lines
     */
    public static function fromPostalLines(array $lines): ?Address
    {
        $addrLines = array_values(array_filter(array_map('trim', $lines), fn (string $l): bool => $l !== ''));
        if ($addrLines === []) {
            return null;
        }
        $zip = null;
        $city = null;

        $postal = self::pullFirstPostalAndCity($addrLines);
        if ($postal !== null) {
            $zip = $postal['zip'];
            $city = $postal['city'];
        }

        if ($addrLines === []) {
            return new Address(
                zip: $zip,
                city: $city,
            );
        }

        $streetAndAdditionalLines = self::postalAddressPartsFromLines($addrLines);

        return new Address(
            street: $streetAndAdditionalLines['street'],
            zip: $zip,
            city: $city,
            addressLine2: $streetAndAdditionalLines['address_line_2'],
            addressLine3: $streetAndAdditionalLines['address_line_3'],
        );
    }

    /**
     * Same as newline text split + {@see fromPostalLines()}, after removing leading lines equal to the party name (e.g. bill customer or supplier).
     * Sets {@see $company} to the trimmed party name when non-empty.
     */
    public static function fromMultilineStringForParty(?string $text, string $partyName): ?Address
    {
        $partyTrim = trim($partyName);
        if ($text === null || trim($text) === '') {
            return $partyTrim !== '' ? new Address($partyTrim) : null;
        }
        $raw = preg_split('/\r\n|\n|\r/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($raw)) {
            return $partyTrim !== '' ? new Address($partyTrim) : null;
        }
        $lines = array_values(array_filter(array_map('trim', $raw), fn (string $l): bool => $l !== ''));
        while ($lines !== [] && $partyTrim !== '' && self::lineMatchesPartyName($lines[0], $partyTrim)) {
            array_shift($lines);
        }
        $postal = self::fromPostalLines($lines);
        if ($postal === null) {
            return $partyTrim !== '' ? new Address($partyTrim) : null;
        }

        return new Address(
            $partyTrim !== '' ? $partyTrim : null,
            $postal->street,
            $postal->zip,
            $postal->city,
            $postal->country,
            $postal->addressLine2,
            $postal->addressLine3,
        );
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

    public static function lineMatchesPartyName(string $line, string $partyName): bool
    {
        $line = trim(preg_replace('/\s+/u', ' ', $line));
        $party = trim(preg_replace('/\s+/u', ' ', $partyName));
        if ($party === '' || $line === '') {
            return false;
        }

        return mb_strtolower($line) === mb_strtolower($party);
    }

    /**
     * First line is always treated as the company / recipient name; postal and street lines are detected by pattern.
     *
     * @param  array<int, string>  $lines
     */
    public static function fromLines(array $lines): ?Address
    {
        $addrLines = array_values(array_filter(array_map('trim', $lines), fn (string $l): bool => $l !== ''));
        if ($addrLines === []) {
            return null;
        }
        $zip = null;
        $city = null;

        $postal = self::pullFirstPostalAndCity($addrLines);
        if ($postal !== null) {
            $zip = $postal['zip'];
            $city = $postal['city'];
        }

        if ($addrLines === []) {
            return new Address(
                zip: $zip,
                city: $city,
            );
        }

        $companyAddressParts = self::companyAddressPartsFromLines($addrLines);

        return new Address(
            company: $companyAddressParts['company'],
            street: $companyAddressParts['street'],
            zip: $zip,
            city: $city,
            addressLine2: $companyAddressParts['address_line_2'],
            addressLine3: $companyAddressParts['address_line_3'],
        );
    }

    /**
     * @param  array<int, string>  $lines
     * @return array<int, string>
     */
    public static function discardedAddressInfoLines(array $lines): array
    {
        $addrLines = array_values(array_filter(array_map('trim', $lines), fn (string $l): bool => $l !== ''));
        if ($addrLines === []) {
            return [];
        }

        self::pullFirstPostalAndCity($addrLines);

        return self::companyAddressPartsFromLines($addrLines)['discarded_address_info_lines'];
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
        $lines = array_values(array_map('trim', $lines));

        return self::fromLines($lines);
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
