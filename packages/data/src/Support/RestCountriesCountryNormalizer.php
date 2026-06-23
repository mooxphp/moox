<?php

declare(strict_types=1);

namespace Moox\Data\Support;

class RestCountriesCountryNormalizer
{
    /**
     * @param  array<string, mixed>  $country
     * @return array<string, mixed>
     */
    public function normalize(array $country): array
    {
        $codes = is_array($country['codes'] ?? null) ? $country['codes'] : [];
        $names = is_array($country['names'] ?? null) ? $country['names'] : [];
        $postalCode = is_array($country['postal_code'] ?? null) ? $country['postal_code'] : [];
        $area = is_array($country['area'] ?? null) ? $country['area'] : [];

        return [
            'cca2' => $this->normalizeAlpha2($codes['alpha_2'] ?? null),
            'cca3' => $codes['alpha_3'] ?? null,
            'name' => [
                'common' => $names['common'] ?? null,
                'nativeName' => $names['native'] ?? [],
            ],
            'altSpellings' => $names['alternates'] ?? [],
            'translations' => $names['translations'] ?? [],
            'region' => $country['region'] ?? null,
            'subregion' => $country['subregion'] ?? null,
            'capital' => $this->normalizeCapitals($country['capitals'] ?? []),
            'population' => $country['population'] ?? null,
            'area' => $area['kilometers'] ?? null,
            'currencies' => $this->normalizeCurrencies($country['currencies'] ?? []),
            'languages' => $this->normalizeLanguages($country['languages'] ?? []),
            'timezones' => is_array($country['timezones'] ?? null) ? $country['timezones'] : [],
            'idd' => $this->normalizeCallingCodes($country['calling_codes'] ?? []),
            'tld' => is_array($country['tlds'] ?? null) ? $country['tlds'] : [],
            'regionalBlocs' => $this->normalizeMemberships($country['memberships'] ?? []),
            'postalCode' => [
                'format' => $postalCode['format'] ?? null,
                'regex' => $postalCode['regex'] ?? null,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    protected function normalizeCapitals(mixed $capitals): array
    {
        if (! is_array($capitals)) {
            return [];
        }

        $names = [];

        foreach ($capitals as $capital) {
            if (is_array($capital) && isset($capital['name']) && is_string($capital['name'])) {
                $names[] = $capital['name'];
            }
        }

        return $names;
    }

    /**
     * @return array<string, array{name: string, symbol: string|null}>
     */
    protected function normalizeCurrencies(mixed $currencies): array
    {
        if (! is_array($currencies)) {
            return [];
        }

        $normalized = [];

        foreach ($currencies as $currency) {
            if (! is_array($currency)) {
                continue;
            }

            $code = $currency['code'] ?? null;

            if (! is_string($code) || $code === '') {
                continue;
            }

            $normalized[$code] = [
                'name' => is_string($currency['name'] ?? null) ? $currency['name'] : '',
                'symbol' => is_string($currency['symbol'] ?? null) ? $currency['symbol'] : null,
            ];
        }

        return $normalized;
    }

    /**
     * @return array<string, string>
     */
    protected function normalizeLanguages(mixed $languages): array
    {
        if (! is_array($languages)) {
            return [];
        }

        $normalized = [];

        foreach ($languages as $language) {
            if (! is_array($language)) {
                continue;
            }

            $alpha3 = $language['iso639_3'] ?? $language['iso639_2b'] ?? null;
            $name = $language['name'] ?? null;

            if (! is_string($alpha3) || $alpha3 === '' || ! is_string($name) || $name === '') {
                continue;
            }

            $normalized[$alpha3] = $name;
        }

        return $normalized;
    }

    /**
     * @return array{root: string|null, suffixes: list<string>}
     */
    protected function normalizeCallingCodes(mixed $callingCodes): array
    {
        if (! is_array($callingCodes) || $callingCodes === []) {
            return [
                'root' => null,
                'suffixes' => [],
            ];
        }

        $primary = (string) reset($callingCodes);
        $suffixes = array_values(array_map(
            static fn (mixed $code): string => (string) $code,
            array_slice($callingCodes, 1)
        ));

        return [
            'root' => $primary !== '' ? '+'.$primary : null,
            'suffixes' => $suffixes,
        ];
    }

    /**
     * @return list<array{acronym: string, name: string}>
     */
    protected function normalizeMemberships(mixed $memberships): array
    {
        if (! is_array($memberships)) {
            return [];
        }

        $normalized = [];

        foreach ($memberships as $key => $value) {
            if ($value !== true || ! is_string($key) || $key === '') {
                continue;
            }

            $normalized[] = [
                'acronym' => strtoupper($key),
                'name' => str_replace('_', ' ', ucwords($key, '_')),
            ];
        }

        return $normalized;
    }

    protected function normalizeAlpha2(mixed $alpha2): ?string
    {
        if (! is_string($alpha2)) {
            return null;
        }

        $alpha2 = strtoupper(trim($alpha2));

        if (strlen($alpha2) !== 2) {
            return null;
        }

        return $alpha2;
    }
}
