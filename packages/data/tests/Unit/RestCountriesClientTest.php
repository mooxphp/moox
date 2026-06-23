<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Moox\Data\Services\RestCountriesClient;
use Moox\Data\Tests\TestCase;

uses(TestCase::class);

test('rest countries client sends bearer token on requests', function (): void {
    Http::fake([
        'api.restcountries.com/countries/v5*' => Http::response([
            'data' => [
                'objects' => [],
                'meta' => [
                    'total' => 0,
                    'count' => 0,
                    'limit' => 100,
                    'offset' => 0,
                    'more' => false,
                ],
            ],
        ]),
    ]);

    app(RestCountriesClient::class)->listAllCountries(['names.common', 'codes.alpha_2']);

    Http::assertSent(function ($request): bool {
        return $request->hasHeader('Authorization', 'Bearer test-api-key')
            && str_contains($request->url(), 'api.restcountries.com/countries/v5');
    });
});

test('rest countries client paginates until all countries are fetched', function (): void {
    Http::fake([
        'api.restcountries.com/countries/v5?limit=100&offset=0*' => Http::response([
            'data' => [
                'objects' => [
                    ['codes' => ['alpha_2' => 'DE']],
                    ['codes' => ['alpha_2' => 'FR']],
                ],
                'meta' => [
                    'total' => 3,
                    'count' => 2,
                    'limit' => 100,
                    'offset' => 0,
                    'more' => true,
                ],
            ],
        ]),
        'api.restcountries.com/countries/v5?limit=100&offset=100*' => Http::response([
            'data' => [
                'objects' => [
                    ['codes' => ['alpha_2' => 'IT']],
                ],
                'meta' => [
                    'total' => 3,
                    'count' => 1,
                    'limit' => 100,
                    'offset' => 100,
                    'more' => false,
                ],
            ],
        ]),
    ]);

    $countries = app(RestCountriesClient::class)->listAllCountries();

    expect($countries)->toHaveCount(3)
        ->and($countries[0]['codes']['alpha_2'])->toBe('DE')
        ->and($countries[2]['codes']['alpha_2'])->toBe('IT');
});

test('rest countries client throws when api key is missing', function (): void {
    config()->set('rest-countries.api_key', null);

    expect(fn () => app(RestCountriesClient::class)->listAllCountries())
        ->toThrow(RuntimeException::class, 'REST Countries API key is not configured');
});
