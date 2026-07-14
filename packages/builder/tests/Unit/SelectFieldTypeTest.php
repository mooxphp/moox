<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/TestItemResource.php';

use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Cache;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Types\SelectFieldType;
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
        'name' => 'karosserieform',
        'label' => 'Karosserieform',
        'type' => 'select',
        'sort' => 0,
        'config' => ['default' => 'limousine'],
        'validation' => ['required' => false, 'rules' => []],
    ])->options()->createMany([
        ['label' => 'Limousine', 'value' => 'limousine', 'sort' => 0],
        ['label' => 'Kombi', 'value' => 'kombi', 'sort' => 1],
    ]);
});

it('applies option defaults for select fields at runtime', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'karosserieform',
        label: 'Karosserieform',
        type: 'select',
        config: ['default' => 'limousine'],
        options: [
            ['label' => 'Limousine', 'value' => 'limousine'],
            ['label' => 'Kombi', 'value' => 'kombi'],
        ],
    );

    $component = (new SelectFieldType)->formComponent($field);

    expect($component->getDefaultState())->toBe('limousine')
        ->and($capability->resolveForField($field))->toBe('limousine')
        ->and($component->isNative())->toBeFalse()
        ->and($component->canSelectPlaceholder())->toBeFalse();
});

it('keeps placeholder selection when no select default is configured', function (): void {
    $field = new FieldDefinition(
        name: 'karosserieform',
        label: 'Karosserieform',
        type: 'select',
        options: [
            ['label' => 'Limousine', 'value' => 'limousine'],
            ['label' => 'Kombi', 'value' => 'kombi'],
        ],
    );

    $component = (new SelectFieldType)->formComponent($field);

    expect($component->getDefaultState())->toBeNull()
        ->and($component->canSelectPlaceholder())->toBeTrue();
});

it('ignores select defaults that are not valid options', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'karosserieform',
        label: 'Karosserieform',
        type: 'select',
        config: ['default' => 'suv'],
        options: [
            ['label' => 'Limousine', 'value' => 'limousine'],
            ['label' => 'Kombi', 'value' => 'kombi'],
        ],
    );

    expect($capability->resolveForField($field))->toBeNull();
});

it('persists configured select defaults when the form value is empty on create', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);

    app(CustomFieldsManager::class)->saveFromFormData(
        TestItemResource::class,
        $record,
        ['karosserieform' => null],
    );

    $stored = FieldValue::query()
        ->forRecord('item', $record->getKey())
        ->where('field_name', 'karosserieform')
        ->first();

    expect($stored?->value_string)->toBe('limousine');
});

it('does not reapply select defaults when an optional value was cleared on update', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);

    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => $record->getKey(),
        'field_name' => 'karosserieform',
        'value_string' => 'kombi',
    ]);

    app(CustomFieldsManager::class)->saveFromFormData(
        TestItemResource::class,
        $record,
        ['karosserieform' => null],
    );

    $stored = FieldValue::query()
        ->forRecord('item', $record->getKey())
        ->where('field_name', 'karosserieform')
        ->first();

    expect($stored?->value_string)->toBeNull();
});

it('syncs select default values through field group persistence', function (): void {
    FieldGroup::query()->where('slug', 'vehicle-options')->delete();
    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $group = FieldGroup::query()->create([
        'name' => 'Sync select',
        'slug' => 'sync-select',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Sync select',
        'slug' => 'sync-select',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'karosserieform',
                'label' => 'Karosserieform',
                'type' => 'select',
                'required' => false,
                'config' => ['default' => 'kombi'],
                'options' => [
                    ['label' => 'Limousine', 'value' => 'limousine'],
                    ['label' => 'Kombi', 'value' => 'kombi'],
                ],
            ],
        ],
    ]);

    expect($group->fields()->where('name', 'karosserieform')->value('config'))->toMatchArray([
        'default' => 'kombi',
    ]);
});

it('resolves select defaults from cached field group definitions', function (): void {
    $field = app(DefinitionRegistry::class)
        ->fieldGroupsFor(TestItemResource::customFieldsLocationContext())
        ->first()
        ?->fields
        ->firstWhere('name', 'karosserieform');

    expect($field)->not->toBeNull()
        ->and(app(DefaultValue::class)->resolveForField($field))->toBe('limousine');
});

it('builds a select for select default values in the admin', function (): void {
    $fields = app(DefaultValue::class)->builderFieldsFor('select');

    expect($fields)->toHaveCount(1)
        ->and($fields[0])->toBeInstanceOf(Select::class)
        ->and($fields[0]->isLive())->toBeTrue();
});
