<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/TestItemResource.php';

use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Cache;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Capabilities\HelperText;
use Moox\Builder\FieldTypes\Types\RadioFieldType;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\Support\TestItemResource;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->createItemsTable();

    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $group = FieldGroup::query()->create([
        'name' => 'Vehicle options',
        'slug' => 'vehicle-options',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'kraftstoff',
        'label' => 'Kraftstoff',
        'type' => 'radio',
        'sort' => 0,
        'config' => ['default' => 'benzin'],
        'validation' => ['required' => false, 'rules' => []],
    ])->options()->createMany([
        ['label' => 'Benzin', 'value' => 'benzin', 'sort' => 0],
        ['label' => 'Diesel', 'value' => 'diesel', 'sort' => 1],
    ]);
});

it('applies option defaults for radio fields at runtime', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'kraftstoff',
        label: 'Kraftstoff',
        type: 'radio',
        config: ['default' => 'benzin'],
        options: [
            ['label' => 'Benzin', 'value' => 'benzin'],
            ['label' => 'Diesel', 'value' => 'diesel'],
        ],
    );

    $component = (new RadioFieldType)->formComponent($field);

    expect($component->getDefaultState())->toBe('benzin')
        ->and($capability->resolveForField($field))->toBe('benzin');
});

it('ignores radio defaults that are not valid options', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'kraftstoff',
        label: 'Kraftstoff',
        type: 'radio',
        config: ['default' => 'lpg'],
        options: [
            ['label' => 'Benzin', 'value' => 'benzin'],
            ['label' => 'Diesel', 'value' => 'diesel'],
        ],
    );

    expect($capability->resolveForField($field))->toBeNull();
});

it('persists configured radio defaults when the form value is empty on create', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);

    app(CustomFieldsManager::class)->saveFromFormData(
        TestItemResource::class,
        $record,
        ['kraftstoff' => null],
    );

    $stored = FieldValue::query()
        ->forRecord('item', $record->getKey())
        ->where('field_name', 'kraftstoff')
        ->first();

    expect($stored?->value_string)->toBe('benzin');
});

it('does not reapply radio defaults when an optional value was cleared on update', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);

    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => $record->getKey(),
        'field_name' => 'kraftstoff',
        'value_string' => 'diesel',
    ]);

    app(CustomFieldsManager::class)->saveFromFormData(
        TestItemResource::class,
        $record,
        ['kraftstoff' => null],
    );

    $stored = FieldValue::query()
        ->forRecord('item', $record->getKey())
        ->where('field_name', 'kraftstoff')
        ->first();

    expect($stored?->value_string)->toBeNull();
});

it('syncs radio default values through field group persistence', function (): void {
    FieldGroup::query()->where('slug', 'vehicle-options')->delete();
    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $group = FieldGroup::query()->create([
        'name' => 'Sync radio',
        'slug' => 'sync-radio',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Sync radio',
        'slug' => 'sync-radio',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'kraftstoff',
                'label' => 'Kraftstoff',
                'type' => 'radio',
                'required' => false,
                'config' => ['default' => 'diesel'],
                'options' => [
                    ['label' => 'Benzin', 'value' => 'benzin'],
                    ['label' => 'Diesel', 'value' => 'diesel'],
                ],
            ],
        ],
    ]);

    expect($group->fields()->where('name', 'kraftstoff')->value('config'))->toMatchArray([
        'default' => 'diesel',
    ]);
});

it('builds a select for radio default values in the admin', function (): void {
    $fields = app(DefaultValue::class)->builderFieldsFor('radio');

    expect($fields)->toHaveCount(1)
        ->and($fields[0])->toBeInstanceOf(Select::class)
        ->and($fields[0]->isLive())->toBeTrue();
});

it('includes helper text capability on radio fields', function (): void {
    expect((new RadioFieldType)->capabilities())
        ->toContain(HelperText::class);
});
