<?php

declare(strict_types=1);

use Moox\KositValidator\Support\InstallerPathSegmentGuard;
use Moox\KositValidator\Tests\TestCase;

uses(TestCase::class);

test('accepts default validator and xrechnung directory names', function (): void {
    expect(InstallerPathSegmentGuard::assertValid('validator', 'paths.validator_dir'))->toBe('validator')
        ->and(InstallerPathSegmentGuard::assertValid('xrechnung', 'paths.xrechnung_dir'))->toBe('xrechnung');
});

test('rejects parent directory traversal segments', function (string $segment): void {
    expect(fn () => InstallerPathSegmentGuard::assertValid($segment, 'paths.validator_dir'))
        ->toThrow(RuntimeException::class, 'must not be');
})->with(['..', '.']);

test('rejects path segments containing separators', function (string $segment): void {
    expect(fn () => InstallerPathSegmentGuard::assertValid($segment, 'paths.validator_dir'))
        ->toThrow(RuntimeException::class, 'single directory name');
})->with(['../evil', 'foo/bar', 'foo\\bar']);

test('rejects empty path segment', function (): void {
    expect(fn () => InstallerPathSegmentGuard::assertValid('', 'paths.validator_dir'))
        ->toThrow(RuntimeException::class, 'must not be empty');
});
