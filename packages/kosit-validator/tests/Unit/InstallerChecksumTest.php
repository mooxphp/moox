<?php

declare(strict_types=1);

use Moox\KositValidator\Support\InstallerChecksum;
use Moox\KositValidator\Tests\TestCase;

uses(TestCase::class);

it('reports install-oriented wording on checksum mismatch', function (): void {
    $path = kositTempPath('checksum').'.jar';
    file_put_contents($path, 'tampered');

    try {
        expect(fn () => InstallerChecksum::assertValid(
            $path,
            hash('sha256', 'expected-bytes'),
            InstallerChecksum::CONTEXT_INSTALL,
        ))->toThrow(RuntimeException::class, 'Aborting; no files installed.');
    } finally {
        @unlink($path);
    }
});

it('reports runtime-oriented wording on checksum mismatch', function (): void {
    $path = kositTempPath('checksum').'.jar';
    file_put_contents($path, 'tampered');

    try {
        expect(fn () => InstallerChecksum::assertValid(
            $path,
            hash('sha256', 'expected-bytes'),
            InstallerChecksum::CONTEXT_RUNTIME,
        ))->toThrow(RuntimeException::class, 'Validation aborted.');
    } finally {
        @unlink($path);
    }
});
