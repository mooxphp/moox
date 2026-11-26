<?php

declare(strict_types=1);

namespace Moox\Organization\Support;

/**
 * Matches a name to one legal form when an official ELF abbreviation or
 * local name is present as a whole phrase. A shorter phrase loses to a
 * longer one, so "GmbH & Co. KG" stays a KG. Shared abbreviations are left
 * unmatched.
 */
final class EvidentLegalFormIndex
{
    /**
     * @param  array<int, array<string, int>>  $byCountry
     * @param  array<string, int>  $global
     */
    private function __construct(
        private array $byCountry,
        private array $global,
    ) {}

    /**
     * @param  array<string, int>  $legalFormIdsByCountryAndCode  Keys are "DE|2HBR".
     * @param  array<string, int>  $countryIdsByAlpha2
     */
    public static function fromCsv(string $csv, array $legalFormIdsByCountryAndCode, array $countryIdsByAlpha2): self
    {
        /** @var array<int, array<string, array<int, true>>> $hits */
        $hits = [];

        foreach (self::rows($csv) as $row) {
            $countryCode = strtoupper(trim($row['country_code']));
            $elfCode = strtoupper(trim($row['elf_code']));
            $countryId = $countryIdsByAlpha2[$countryCode] ?? null;
            $legalFormId = $legalFormIdsByCountryAndCode[$countryCode.'|'.$elfCode] ?? null;

            if ($countryId === null || $legalFormId === null || $row['status'] !== 'ACTV' || in_array($elfCode, ['8888', '9999'], true)) {
                continue;
            }

            foreach ($row['phrases'] as $phrase) {
                $normalized = self::normalize($phrase);

                if (mb_strlen(str_replace(' ', '', $normalized)) < 2) {
                    continue;
                }

                $hits[$countryId][$normalized][$legalFormId] = true;
            }
        }

        $byCountry = [];
        $globalHits = [];

        foreach ($hits as $countryId => $phrases) {
            foreach ($phrases as $phrase => $legalFormIds) {
                if (count($legalFormIds) !== 1) {
                    continue;
                }

                $legalFormId = (int) array_key_first($legalFormIds);
                $byCountry[$countryId][$phrase] = $legalFormId;
                $globalHits[$phrase][$legalFormId] = true;
            }
        }

        $global = [];

        foreach ($globalHits as $phrase => $legalFormIds) {
            if (count($legalFormIds) === 1) {
                $global[$phrase] = (int) array_key_first($legalFormIds);
            }
        }

        return new self($byCountry, $global);
    }

    public function match(?int $countryId, string $name): ?int
    {
        $normalized = self::normalize($name);

        if ($normalized === '') {
            return null;
        }

        $phrases = $countryId !== null
            ? ($this->byCountry[$countryId] ?? [])
            : $this->global;

        return self::longestMatch($normalized, $phrases);
    }

    public static function normalize(string $value): string
    {
        $value = mb_strtolower($value);
        $value = str_replace(['&', '+'], ' ', $value);
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);

        if ($value === '') {
            return '';
        }

        $collapsed = [];
        $singleLetters = '';

        foreach (explode(' ', $value) as $token) {
            if (mb_strlen($token) === 1) {
                $singleLetters .= $token;

                continue;
            }

            if ($singleLetters !== '') {
                $collapsed[] = $singleLetters;
                $singleLetters = '';
            }

            $collapsed[] = $token;
        }

        if ($singleLetters !== '') {
            $collapsed[] = $singleLetters;
        }

        return implode(' ', $collapsed);
    }

    /**
     * @param  array<string, int>  $phrases
     */
    private static function longestMatch(string $normalizedName, array $phrases): ?int
    {
        $haystack = ' '.$normalizedName.' ';
        $bestLength = 0;
        $matches = [];

        foreach ($phrases as $phrase => $legalFormId) {
            if (! str_contains($haystack, ' '.$phrase.' ')) {
                continue;
            }

            $length = mb_strlen($phrase);

            if ($length > $bestLength) {
                $bestLength = $length;
                $matches = [$legalFormId];

                continue;
            }

            if ($length === $bestLength && ! in_array($legalFormId, $matches, true)) {
                $matches[] = $legalFormId;
            }
        }

        return count($matches) === 1 ? $matches[0] : null;
    }

    /**
     * @return list<array{elf_code: string, country_code: string, status: string, phrases: list<string>}>
     */
    private static function rows(string $csv): array
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return [];
        }

        fwrite($handle, $csv);
        rewind($handle);

        $header = fgetcsv($handle, null, ',', '"', '\\');

        if (! is_array($header)) {
            fclose($handle);

            return [];
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]) ?? (string) $header[0];
        $columns = array_flip($header);

        foreach (['ELF Code', 'Country Code (ISO 3166-1)', 'ELF Status ACTV/INAC', 'Abbreviations Local language', 'Abbreviations transliterated', 'Entity Legal Form name Local name'] as $column) {
            if (! array_key_exists($column, $columns)) {
                fclose($handle);

                return [];
            }
        }

        $rows = [];

        while (($record = fgetcsv($handle, null, ',', '"', '\\')) !== false) {
            $elfCode = (string) ($record[$columns['ELF Code']] ?? '');
            $phrases = array_filter(array_merge(
                explode(';', (string) ($record[$columns['Abbreviations Local language']] ?? '')),
                explode(';', (string) ($record[$columns['Abbreviations transliterated']] ?? '')),
                [(string) ($record[$columns['Entity Legal Form name Local name']] ?? '')],
            ), fn (string $phrase): bool => trim($phrase) !== '');

            $rows[] = [
                'elf_code' => $elfCode,
                'country_code' => (string) ($record[$columns['Country Code (ISO 3166-1)']] ?? ''),
                'status' => (string) ($record[$columns['ELF Status ACTV/INAC']] ?? ''),
                'phrases' => array_values($phrases),
            ];
        }

        fclose($handle);

        return $rows;
    }
}
