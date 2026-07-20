<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Moox\VeraPdf\Support\InstallerChecksum;
use Moox\VeraPdf\Tests\TestCase;

uses(TestCase::class);

it('passes when the file matches the expected sha256', function (): void {
    $path = tempFileWithContent('checksum', 'trusted-installer-bytes');
    $expected = hash('sha256', 'trusted-installer-bytes');

    expect(fn () => InstallerChecksum::assertMatches($path, $expected))
        ->not->toThrow(Throwable::class);

    File::delete($path);
});

it('throws when the checksum does not match', function (): void {
    $path = tempFileWithContent('checksum', 'tampered');

    expect(fn () => InstallerChecksum::assertMatches($path, str_repeat('a', 64)))
        ->toThrow(RuntimeException::class, 'checksum mismatch');

    File::delete($path);
});

it('throws when the expected checksum is empty', function (): void {
    $path = tempFileWithContent('checksum', 'bytes');

    expect(fn () => InstallerChecksum::assertMatches($path, ''))
        ->toThrow(RuntimeException::class, 'checksum is not configured');

    File::delete($path);
});

it('throws when the expected checksum is malformed', function (): void {
    $path = tempFileWithContent('checksum', 'bytes');

    expect(fn () => InstallerChecksum::assertMatches($path, 'not-a-sha256'))
        ->toThrow(RuntimeException::class, 'checksum is not configured');

    File::delete($path);
});

it('throws when the file is missing', function (): void {
    $path = verapdfTempPath('checksum-missing').'.bin';

    expect(fn () => InstallerChecksum::assertMatches($path, str_repeat('b', 64)))
        ->toThrow(RuntimeException::class, 'Cannot verify checksum');
});
