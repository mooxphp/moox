<?php

declare(strict_types=1);

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Moox\KositValidator\Support\InstallerDownloadHttpOptions;
use Moox\KositValidator\Tests\TestCase;

uses(TestCase::class);

test('download refuses https to http redirect via guzzle redirect middleware', function (): void {
    $mock = new MockHandler([
        new Response(302, ['Location' => 'http://evil.test/validator-1.6.2-standalone.jar']),
    ]);
    $stack = HandlerStack::create($mock);
    $target = kositTempPath('redirect-downgrade');

    expect(fn () => Http::timeout(600)
        ->sink($target)
        ->withOptions(array_merge(
            InstallerDownloadHttpOptions::guzzle(),
            ['handler' => $stack],
        ))
        ->get('https://example.test/validator-1.6.2-standalone.jar'))
        ->toThrow(function (ConnectionException $exception): bool {
            return $exception->getPrevious() instanceof BadResponseException;
        });

    if (is_file($target)) {
        unlink($target);
    }
});

test('download follows https to https redirect via guzzle redirect middleware', function (): void {
    $jarBytes = 'valid-jar-bytes';
    $mock = new MockHandler([
        new Response(302, ['Location' => 'https://example.test/final/validator-1.6.2-standalone.jar']),
        new Response(200, [], $jarBytes),
    ]);
    $stack = HandlerStack::create($mock);
    $target = kositTempPath('redirect-success');

    $response = Http::timeout(600)
        ->sink($target)
        ->withOptions(array_merge(
            InstallerDownloadHttpOptions::guzzle(),
            ['handler' => $stack],
        ))
        ->get('https://example.test/validator-1.6.2-standalone.jar');

    expect($response->successful())->toBeTrue()
        ->and(is_file($target))->toBeTrue()
        ->and((string) file_get_contents($target))->toBe($jarBytes);

    unlink($target);
});
