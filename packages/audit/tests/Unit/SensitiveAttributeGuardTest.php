<?php

declare(strict_types=1);

use Moox\Audit\Support\SensitiveAttributeGuard;
use Moox\Audit\Tests\TestCase;

uses(TestCase::class);

it('detects sensitive keys by exact and substring match', function (): void {
    config()->set('audit.mask_attributes', ['password', 'api_key']);

    expect(SensitiveAttributeGuard::shouldMaskKey('password'))->toBeTrue()
        ->and(SensitiveAttributeGuard::shouldMaskKey('new_password'))->toBeTrue()
        ->and(SensitiveAttributeGuard::shouldMaskKey('api_key'))->toBeTrue()
        ->and(SensitiveAttributeGuard::shouldMaskKey('stripe_api_key'))->toBeTrue()
        ->and(SensitiveAttributeGuard::shouldMaskKey('title'))->toBeFalse();
});

it('masks sensitive values while keeping other keys intact', function (): void {
    config()->set('audit.mask_attributes', ['password', 'token']);

    expect(SensitiveAttributeGuard::maskValues([
        'title' => 'Hello',
        'password' => 'secret',
        'access_token' => 'abc123',
        'status' => null,
    ]))->toBe([
        'title' => 'Hello',
        'password' => SensitiveAttributeGuard::MASK,
        'access_token' => SensitiveAttributeGuard::MASK,
        'status' => null,
    ]);
});

it('masks old and attributes sections of a changes payload', function (): void {
    config()->set('audit.mask_attributes', ['password']);

    expect(SensitiveAttributeGuard::maskChanges([
        'old' => ['password' => 'old-secret', 'title' => 'A'],
        'attributes' => ['password' => 'new-secret', 'title' => 'B'],
    ]))->toBe([
        'old' => ['password' => SensitiveAttributeGuard::MASK, 'title' => 'A'],
        'attributes' => ['password' => SensitiveAttributeGuard::MASK, 'title' => 'B'],
    ]);
});
