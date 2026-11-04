<?php

declare(strict_types=1);

use Moox\Transform\Support\MappedExpression;
use Tests\TestCase;

uses(TestCase::class);

test('it reads a string mapping as the expression', function (): void {
    expect(MappedExpression::path('legacy.Street'))->toBe('legacy.Street')
        ->and(MappedExpression::note('legacy.Street'))->toBeNull();
});

test('it reads expression and note from a documented mapping', function (): void {
    $stored = MappedExpression::store(
        'lookup_id:Contact,external_reference,legacy.ParentGuid,with_trashed',
        'Contact über ParentGuid, auch gelöschte.',
    );

    expect($stored)->toBe([
        'expression' => 'lookup_id:Contact,external_reference,legacy.ParentGuid,with_trashed',
        'note' => 'Contact über ParentGuid, auch gelöschte.',
    ])
        ->and(MappedExpression::path($stored))->toBe('lookup_id:Contact,external_reference,legacy.ParentGuid,with_trashed')
        ->and(MappedExpression::note($stored))->toBe('Contact über ParentGuid, auch gelöschte.');
});

test('it keeps a string when the note is empty', function (): void {
    expect(MappedExpression::store('legacy.Street', '  '))->toBe('legacy.Street');
});

test('it round-trips form rows', function (): void {
    expect(MappedExpression::fromFormRow([
        'source_path' => 'legacy.Street',
        'note' => '',
    ]))->toBe('legacy.Street')
        ->and(MappedExpression::fromFormRow([
            'source_path' => 'lookup_address_by_legacy:legacy',
            'note' => '  Kanonische Adresse über Id, Hash, Fingerprint.  ',
        ]))->toBe([
            'expression' => 'lookup_address_by_legacy:legacy',
            'note' => 'Kanonische Adresse über Id, Hash, Fingerprint.',
        ])
        ->and(MappedExpression::toFormRow([
            'expression' => 'lookup_address_by_legacy:legacy',
            'note' => 'Kanonische Adresse über Id, Hash, Fingerprint.',
        ]))->toBe([
            'source_path' => 'lookup_address_by_legacy:legacy',
            'note' => 'Kanonische Adresse über Id, Hash, Fingerprint.',
        ]);
});
