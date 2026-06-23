<?php

declare(strict_types=1);

use Moox\Data\Support\RestCountriesCountryNormalizer;

test('rest countries country normalizer maps v5 payload to legacy import shape', function (): void {
    $normalized = (new RestCountriesCountryNormalizer)->normalize([
        'names' => [
            'common' => 'Germany',
            'native' => [
                'deu' => [
                    'common' => 'Deutschland',
                    'official' => 'Bundesrepublik Deutschland',
                ],
            ],
            'alternates' => ['Federal Republic of Germany'],
            'translations' => [
                'deu' => [
                    'common' => 'Deutschland',
                    'official' => 'Bundesrepublik Deutschland',
                ],
            ],
        ],
        'codes' => [
            'alpha_2' => 'DE',
            'alpha_3' => 'DEU',
        ],
        'region' => 'Europe',
        'subregion' => 'Western Europe',
        'capitals' => [
            ['name' => 'Berlin'],
        ],
        'population' => 83200000,
        'area' => [
            'kilometers' => 357114,
        ],
        'currencies' => [
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        ],
        'languages' => [
            [
                'iso639_3' => 'deu',
                'name' => 'German',
            ],
        ],
        'timezones' => ['UTC+01:00'],
        'calling_codes' => ['49'],
        'tlds' => ['.de'],
        'memberships' => [
            'eu' => true,
            'nato' => true,
            'un' => true,
        ],
        'postal_code' => [
            'format' => '#####',
            'regex' => '^\\d{5}$',
        ],
    ]);

    expect($normalized['cca2'])->toBe('DE')
        ->and($normalized['cca3'])->toBe('DEU')
        ->and($normalized['name']['common'])->toBe('Germany')
        ->and($normalized['capital'])->toBe(['Berlin'])
        ->and($normalized['area'])->toBe(357114)
        ->and($normalized['currencies'])->toBe([
            'EUR' => ['name' => 'Euro', 'symbol' => '€'],
        ])
        ->and($normalized['languages'])->toBe(['deu' => 'German'])
        ->and($normalized['idd']['root'])->toBe('+49')
        ->and($normalized['regionalBlocs'])->toHaveCount(3)
        ->and($normalized['postalCode']['format'])->toBe('#####');
});

test('rest countries country normalizer rejects invalid alpha2 codes', function (): void {
    $normalized = (new RestCountriesCountryNormalizer)->normalize([
        'names' => ['common' => 'South Ossetia'],
        'codes' => ['alpha_2' => '', 'alpha_3' => null],
    ]);

    expect($normalized['cca2'])->toBeNull();
});
