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
