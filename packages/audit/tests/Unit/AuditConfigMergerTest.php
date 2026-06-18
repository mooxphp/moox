<?php

declare(strict_types=1);

use Moox\Audit\Support\AuditConfigMerger;
use Moox\Audit\Support\AuditConfigResolver;
use Moox\Audit\Support\AuditPackageRegistry;
use Moox\Audit\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    AuditPackageRegistry::clear();
});

it('merges preset package and app config for models', function (): void {
    config([
        'audit.presets.draft_main' => [
            'entry_type' => 'audit',
            'events' => ['created', 'updated'],
        ],
    ]);

    AuditPackageRegistry::register('category', [
        'models' => [
            'App\\Models\\Item' => [
                'preset' => 'draft_main',
                'log_name' => 'category',
                'attributes' => ['status', 'scope', 'color'],
            ],
        ],
    ]);

    config([
        'audit.models' => [
            'App\\Models\\Item' => [
                'attributes' => ['status', 'scope'],
            ],
        ],
    ]);

    $resolved = AuditConfigResolver::resolveModel('App\\Models\\Item');

    expect($resolved)->not->toBeNull()
        ->and($resolved['log_name'])->toBe('category')
        ->and($resolved['entry_type'])->toBe('audit')
        ->and($resolved['attributes'])->toBe(['status', 'scope'])
        ->and($resolved['events'])->toBe(['created', 'updated']);
});

it('appends attributes when append_attributes is set', function (): void {
    $merged = AuditConfigMerger::merge(
        [],
        ['attributes' => ['status', 'scope']],
        ['append_attributes' => ['due_at']],
    );

    expect($merged['attributes'])->toBe(['status', 'scope', 'due_at']);
});

it('disables a model when app override sets enabled false', function (): void {
    AuditPackageRegistry::register('category', [
        'models' => [
            'App\\Models\\Item' => [
                'enabled' => true,
                'log_name' => 'category',
                'attributes' => ['status'],
            ],
        ],
    ]);

    config([
        'audit.models' => [
            'App\\Models\\Item' => [
                'enabled' => false,
            ],
        ],
    ]);

    expect(AuditConfigResolver::resolveModel('App\\Models\\Item'))->toBeNull();
});

it('disables hooks when app override sets enabled false', function (): void {
    AuditPackageRegistry::register('category', [
        'hooks' => [
            'App\\Models\\Item' => [
                'deleting' => [
                    'handler' => 'categorizables_detached',
                    'log_name' => 'category',
                ],
            ],
        ],
    ]);

    config([
        'audit.hooks' => [
            'App\\Models\\Item' => [
                'deleting' => [
                    'enabled' => false,
                ],
            ],
        ],
    ]);

    expect(AuditConfigResolver::resolvedHooks())->toBe([]);
});
