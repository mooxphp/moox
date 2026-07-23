<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';

use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Types\MessageFieldType;
use Moox\Builder\FieldTypes\Types\OembedFieldType;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

it('does not persist message field values', function (): void {
    $this->createItemsTable();

    $record = TestItem::query()->create(['title' => 'Demo']);
    $fields = collect([
        new FieldDefinition(
            name: 'hint',
            label: 'Hint',
            type: 'message',
            config: ['message' => 'Read-only hint'],
        ),
    ]);

    app(CustomFieldsManager::class)->saveValues('item', $record, [
        'hint' => 'should-not-save',
    ], $fields);

    expect(FieldValue::query()->count())->toBe(0)
        ->and((new MessageFieldType)->storesValue())->toBeFalse();
});

it('persists oembed urls as strings', function (): void {
    $this->createItemsTable();

    $record = TestItem::query()->create(['title' => 'Demo']);
    $fields = collect([
        new FieldDefinition('video', 'Video', 'oembed'),
    ]);

    app(CustomFieldsManager::class)->saveValues('item', $record, [
        'video' => 'https://www.youtube.com/watch?v=demo',
    ], $fields);

    $stored = FieldValue::query()->forRecord('item', $record->getKey())->first();

    expect($stored?->value_string)->toBe('https://www.youtube.com/watch?v=demo')
        ->and((new OembedFieldType)->storesValue())->toBeTrue();
});
