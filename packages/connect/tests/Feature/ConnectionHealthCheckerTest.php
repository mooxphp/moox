<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Moox\Connect\Models\ApiConnection;

test('healthcheck defaults to /health when health_path is not configured', function (): void {
    $callCount = 0;

    Http::fake(function () use (&$callCount) {
        $callCount++;

        return Http::response(['ok' => true], 200);
    });

    ApiConnection::factory()->create([
        'api_type' => 'REST',
        'base_url' => 'https://example.com',
        'health_path' => null,
        'auth_type' => 'None',
        'auth_credentials' => null,
        'status' => 'New',
    ]);

    expect($callCount)->toBe(1);
});

test('no additional healthcheck when auth credentials change but auth_type is None', function (): void {
    $callCount = 0;

    Http::fake(function () use (&$callCount) {
        $callCount++;

        return Http::response(['ok' => true], 200);
    });

    $connection = ApiConnection::factory()->create([
        'api_type' => 'REST',
        'base_url' => 'https://example.com',
        'health_path' => null,
        'auth_type' => 'None',
        'auth_credentials' => null,
        'status' => 'New',
    ]);

    expect($callCount)->toBe(1);

    // Simulate a "second save" where auth_credentials get updated,
    // but auth_type remains None (auth disabled).
    $connection->auth_credentials = ['dummy' => 'value'];
    $connection->save();

    expect($callCount)->toBe(1);
});
