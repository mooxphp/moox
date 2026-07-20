<?php

declare(strict_types=1);

use Moox\KositValidator\Support\InstallerBasePathGuard;
use Moox\KositValidator\Tests\TestCase;

uses(TestCase::class);

test('accepts default base path under storage root', function (): void {
    config()->set('kosit-validator.installer.allow_untrusted_base_path', false);
    config()->set('kosit-validator.installer.storage_root', storage_path('app/private'));

    InstallerBasePathGuard::assertValid(storage_path('app/private/kosit'));

    expect(true)->toBeTrue();
});

test('rejects base path outside storage root', function (): void {
    config()->set('kosit-validator.installer.allow_untrusted_base_path', false);
    config()->set('kosit-validator.installer.storage_root', storage_path('app/private'));

    expect(fn () => InstallerBasePathGuard::assertValid('/tmp/kosit-evil'))
        ->toThrow(RuntimeException::class, 'must be under');
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
