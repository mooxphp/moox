<?php

declare(strict_types=1);

use Moox\KositValidator\Support\InstallerDownloadUrlGuard;
use Moox\KositValidator\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config()->set('kosit-validator.installer.allow_untrusted_download_hosts', false);
});

test('accepts default validator github release url', function (): void {
    InstallerDownloadUrlGuard::assertValid(
        KOSIT_TEST_VALIDATOR_DOWNLOAD_URL,
        'Validator v1.6.2',
    );

    expect(true)->toBeTrue();
});

test('accepts default xrechnung github release url', function (): void {
    InstallerDownloadUrlGuard::assertValid(
        KOSIT_TEST_XRECHNUNG_DOWNLOAD_URL,
        'XRechnung Configuration v3.0.2',
    );

    expect(true)->toBeTrue();
});

test('rejects non-https url', function (): void {
    expect(fn () => InstallerDownloadUrlGuard::assertValid(
        'http://github.com/itplr-kosit/validator/releases/download/v1.6.2/validator-1.6.2-standalone.jar',
        'Validator v1.6.2',
    ))->toThrow(RuntimeException::class, 'must use HTTPS');
});

test('rejects untrusted host when allow flag false', function (): void {
    expect(fn () => InstallerDownloadUrlGuard::assertValid(
        'https://evil.test/validator-1.6.2-standalone.jar',
        'Validator v1.6.2',
    ))->toThrow(RuntimeException::class, 'is not allowed');
});

test('allows example.test when allow flag true', function (): void {
    config()->set('kosit-validator.installer.allow_untrusted_download_hosts', true);

    InstallerDownloadUrlGuard::assertValid(
        'https://example.test/validator-1.6.2-standalone.jar',
        'Validator v1.6.2',
    );

    expect(true)->toBeTrue();
});

test('rejects github url outside itplr-kosit prefixes', function (): void {
    expect(fn () => InstallerDownloadUrlGuard::assertValid(
        'https://github.com/other-org/repo/releases/download/v1.0.0/artifact.jar',
        'Validator v1.6.2',
    ))->toThrow(RuntimeException::class, 'not an allowed itplr-kosit release');
});
