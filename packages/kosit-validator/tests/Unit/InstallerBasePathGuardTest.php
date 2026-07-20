<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Moox\KositValidator\Support\InstallerBasePathGuard;
use Moox\KositValidator\Tests\TestCase;

uses(TestCase::class);

test('accepts default base path under storage root', function (): void {
    config()->set('kosit-validator.installer.allow_untrusted_base_path', false);
    config()->set('kosit-validator.installer.storage_root', storage_path('app/private'));

    InstallerBasePathGuard::assertValid(storage_path('app/private/kosit'));

    expect(true)->toBeTrue();
});

test('accepts non-existent base path under storage root lexically', function (): void {
    config()->set('kosit-validator.installer.allow_untrusted_base_path', false);
    config()->set('kosit-validator.installer.storage_root', storage_path('app/private'));

    $basePath = storage_path('app/private/kosit-first-install-'.uniqid('', true));

    InstallerBasePathGuard::assertValid($basePath);

    expect(is_dir($basePath))->toBeFalse();
});

test('accepts existing base path under storage root', function (): void {
    config()->set('kosit-validator.installer.allow_untrusted_base_path', false);
    config()->set('kosit-validator.installer.storage_root', storage_path('app/private'));

    $basePath = storage_path('app/private/kosit-existing-'.uniqid('', true));
    File::ensureDirectoryExists($basePath);

    try {
        InstallerBasePathGuard::assertValid($basePath);

        expect(true)->toBeTrue();
    } finally {
        File::deleteDirectory($basePath);
    }
});

test('rejects base path outside storage root', function (): void {
    config()->set('kosit-validator.installer.allow_untrusted_base_path', false);
    config()->set('kosit-validator.installer.storage_root', storage_path('app/private'));

    expect(fn () => InstallerBasePathGuard::assertValid('/tmp/kosit-evil'))
        ->toThrow(RuntimeException::class, 'must be under');
});

test('rejects symlink base path that resolves outside storage root', function (): void {
    config()->set('kosit-validator.installer.allow_untrusted_base_path', false);
    config()->set('kosit-validator.installer.storage_root', storage_path('app/private'));

    $outside = sys_get_temp_dir().'/kosit-outside-'.uniqid('', true);
    $link = storage_path('app/private/kosit-link-'.uniqid('', true));

    File::ensureDirectoryExists($outside);
    File::ensureDirectoryExists(dirname($link));

    if (! @symlink($outside, $link)) {
        test()->markTestSkipped('symlink not supported');
    }

    try {
        expect(fn () => InstallerBasePathGuard::assertValid($link))
            ->toThrow(RuntimeException::class, 'must be under');
    } finally {
        @unlink($link);
        File::deleteDirectory($outside);
    }
});

test('allows arbitrary base path when untrusted flag enabled', function (): void {
    config()->set('kosit-validator.installer.allow_untrusted_base_path', true);

    InstallerBasePathGuard::assertValid('/tmp/kosit-evil');

    expect(true)->toBeTrue();
});

test('rejects empty base path', function (): void {
    config()->set('kosit-validator.installer.allow_untrusted_base_path', false);

    expect(fn () => InstallerBasePathGuard::assertValid(''))
        ->toThrow(RuntimeException::class, 'must not be empty');
});
