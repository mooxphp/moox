<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';

use Illuminate\Support\Facades\DB;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Support\TypedValueColumns;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->createItemsTable();
});

it('loads custom field values once per record when using the hydration cache', function (): void {
    $record = TestItem::query()->create(['id' => 1, 'title' => 'Demo']);

    foreach (['first', 'second', 'third'] as $name) {
        FieldValue::query()->create([
            'entity' => 'item',
            'record_id' => $record->getKey(),
            ...TypedValueColumns::attributesFor('text', "value-{$name}"),
            'field_name' => $name,
        ]);
    }

    $fields = collect([
        new FieldDefinition(name: 'first', label: 'First', type: 'text'),
        new FieldDefinition(name: 'second', label: 'Second', type: 'text'),
        new FieldDefinition(name: 'third', label: 'Third', type: 'text'),
    ]);

    $manager = app(CustomFieldsManager::class);

    DB::enableQueryLog();

    $manager->loadCachedValues('item', $record, $fields);
    $manager->loadCachedValues('item', $record, $fields);

    $valueQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'builder_field_values'));

    expect($valueQueries)->toHaveCount(1)
        ->and($manager->loadCachedValues('item', $record, $fields))->toMatchArray([
            'first' => 'value-first',
            'second' => 'value-second',
            'third' => 'value-third',
        ]);
});

it('invalidates the hydration cache after saving values', function (): void {
    $record = TestItem::query()->create(['id' => 1, 'title' => 'Demo']);

    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => $record->getKey(),
        ...TypedValueColumns::attributesFor('text', 'old'),
        'field_name' => 'title',
    ]);

    $field = new FieldDefinition(name: 'title', label: 'Title', type: 'text');
    $fields = collect([$field]);
    $manager = app(CustomFieldsManager::class);

    expect($manager->loadCachedValues('item', $record, $fields))->toBe(['title' => 'old']);

    $manager->saveValues('item', $record, ['title' => 'new'], $fields);

    expect($manager->loadCachedValues('item', $record, $fields))->toBe(['title' => 'new']);
});

it('skips validation for fields hidden by conditional logic', function (): void {
    $record = TestItem::query()->create(['id' => 1, 'title' => 'Demo']);

    $fields = collect([
        new FieldDefinition(
            name: 'customer_type',
            label: 'Customer type',
            type: 'text',
        ),
        new FieldDefinition(
            name: 'company',
            label: 'Company',
            type: 'text',
            validation: ['required' => true, 'rules' => []],
            settings: [
                'conditions' => [
                    'enabled' => true,
                    'action' => 'show',
                    'logic' => 'and',
                    'rules' => [
                        ['field' => 'customer_type', 'operator' => 'equals', 'value' => 'business'],
                    ],
                ],
            ],
        ),
    ]);

    $manager = app(CustomFieldsManager::class);

    $manager->saveValues('item', $record, [
        'customer_type' => 'private',
        'company' => 'injected-value',
    ], $fields);

    expect(FieldValue::query()->forRecord('item', $record->getKey())->pluck('field_name')->all())
        ->toContain('customer_type', 'company');

    // Hidden field must not persist the submitted (unvalidated) value.
    $companyRow = FieldValue::query()
        ->forRecord('item', $record->getKey())
        ->where('field_name', 'company')
        ->first();

    expect($companyRow?->value_string)->toBeNull();
});
