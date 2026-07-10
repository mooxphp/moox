<?php

declare(strict_types=1);

use Moox\Static\Models\StaticEntry;
use Moox\Static\Tests\TestCase;

uses(TestCase::class);

test('static entry can be created with translations', function (): void {
    $entry = StaticEntry::factory()->create([
        'code' => 'TEST-001',
    ]);

    expect($entry)->toBeInstanceOf(StaticEntry::class);
    expect($entry->code)->toBe('TEST-001');
    expect($entry->hasTranslation('en_US'))->toBeTrue();
    expect($entry->hasTranslation('de_DE'))->toBeTrue();
    expect($entry->translate('en_US')?->common_name)->toBeString();
    expect($entry->translate('de_DE')?->common_name)->toBeString();
});

test('static entry translation can be updated', function (): void {
    $entry = StaticEntry::factory()->create();

    $entry->translateOrNew('en_US')->fill([
        'common_name' => 'Updated Name',
        'description' => 'Updated description',
    ]);
    $entry->save();
    $entry->refresh();

    expect($entry->translate('en_US')?->common_name)->toBe('Updated Name');
    expect($entry->translate('en_US')?->description)->toBe('Updated description');
});
