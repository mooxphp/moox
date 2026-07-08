<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Moox\Data\Jobs\ImportStaticDataJob;
use Moox\Data\Models\StaticCountry;
use Moox\Data\Models\StaticCurrency;
use Moox\Data\Tests\TestCase;

uses(TestCase::class);

test('import static data job imports countries using authenticated rest countries requests', function (): void {
    Http::fake([
        'api.restcountries.com/countries/v5*' => Http::response([
            'data' => [
                'objects' => [
                    [
                        'names' => [
                            'common' => 'Germany',
                            'native' => [],
                            'alternates' => [],
                            'translations' => [],
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
                        'area' => ['kilometers' => 357114],
                        'currencies' => [
                            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
                        ],
                        'languages' => [
                            ['iso639_3' => 'deu', 'name' => 'German'],
                        ],
                        'timezones' => ['UTC+01:00'],
                        'calling_codes' => ['49'],
                        'tlds' => ['.de'],
                        'memberships' => ['eu' => true],
                        'postal_code' => ['format' => '#####', 'regex' => '^\\d{5}$'],
                    ],
                ],
                'meta' => [
                    'total' => 1,
                    'count' => 1,
                    'limit' => 100,
                    'offset' => 0,
                    'more' => false,
                ],
            ],
        ]),
        'www.apicountries.com/*' => Http::response([]),
    ]);

    (new ImportStaticDataJob)->handle();

    expect(StaticCountry::query()->where('alpha2', 'DE')->value('common_name'))->toBe('Germany')
        ->and(StaticCurrency::query()->where('code', 'EUR')->exists())->toBeTrue();

    Http::assertSent(function ($request): bool {
        return str_contains($request->url(), 'api.restcountries.com/countries/v5')
            && $request->hasHeader('Authorization', 'Bearer test-api-key');
    });
});

test('import static data job keeps german and swiss german as separate language records', function (): void {
    Http::fake([
        'api.restcountries.com/countries/v5*' => Http::response([
            'data' => [
                'objects' => [
                    [
                        'names' => ['common' => 'Germany', 'native' => [], 'alternates' => [], 'translations' => []],
                        'codes' => ['alpha_2' => 'DE', 'alpha_3' => 'DEU'],
                        'region' => 'Europe',
                        'subregion' => 'Western Europe',
                        'capitals' => [['name' => 'Berlin']],
                        'population' => 83200000,
                        'area' => ['kilometers' => 357114],
                        'currencies' => [],
                        'languages' => [['iso639_3' => 'deu', 'name' => 'German']],
                        'timezones' => [],
                        'calling_codes' => ['49'],
                        'tlds' => ['.de'],
                        'memberships' => [],
                        'postal_code' => ['format' => null, 'regex' => null],
                    ],
                    [
                        'names' => ['common' => 'Switzerland', 'native' => [], 'alternates' => [], 'translations' => []],
                        'codes' => ['alpha_2' => 'CH', 'alpha_3' => 'CHE'],
                        'region' => 'Europe',
                        'subregion' => 'Western Europe',
                        'capitals' => [['name' => 'Bern']],
                        'population' => 8700000,
                        'area' => ['kilometers' => 41284],
                        'currencies' => [],
                        'languages' => [
                            ['iso639_3' => 'gsw', 'name' => 'Swiss German'],
                            ['iso639_3' => 'deu', 'name' => 'German'],
                        ],
                        'timezones' => [],
                        'calling_codes' => ['41'],
                        'tlds' => ['.ch'],
                        'memberships' => [],
                        'postal_code' => ['format' => null, 'regex' => null],
                    ],
                ],
                'meta' => [
                    'total' => 2,
                    'count' => 2,
                    'limit' => 100,
                    'offset' => 0,
                    'more' => false,
                ],
            ],
        ]),
        'www.apicountries.com/*' => Http::response([
            ['alpha2Code' => 'DE', 'languages' => [['iso639_1' => 'de', 'nativeName' => 'Deutsch']]],
        ]),
    ]);

    (new ImportStaticDataJob)->handle();

    $german = \Moox\Data\Models\StaticLanguage::query()->where('alpha2', 'de')->first();
    $swissGerman = \Moox\Data\Models\StaticLanguage::query()->where('alpha2', 'gsw')->first();

    expect($german)->not->toBeNull()
        ->and($german->common_name)->toBe('German')
        ->and($german->alpha3_b)->toBe('deu')
        ->and($german->native_name)->toBe('Deutsch')
        ->and($swissGerman)->not->toBeNull()
        ->and($swissGerman->common_name)->toBe('Swiss German')
        ->and($swissGerman->alpha3_b)->toBe('gsw')
        ->and(\Moox\Data\Models\StaticLocale::query()->where('locale', 'de_CH')->value('name'))->toBe('German')
        ->and(\Moox\Data\Models\StaticLocale::query()->where('locale', 'gsw_CH')->value('name'))->toBe('Swiss German');
});
