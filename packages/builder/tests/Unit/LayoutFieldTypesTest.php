<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';

use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Fieldset;
use Illuminate\Support\Facades\Cache;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Data\FieldGroupDefinition;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Registry\FieldTypeRegistry;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\Support\TestItemResource;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->createItemsTable();
});

it('syncs nested subfields for group and repeater fields', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Nested',
        'slug' => 'nested',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Nested',
        'slug' => 'nested',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'address',
                'label' => 'Address',
                'type' => 'group',
                'required' => false,
                'children' => [
                    ['name' => 'street', 'label' => 'Street', 'type' => 'text', 'required' => false],
                    ['name' => 'city', 'label' => 'City', 'type' => 'text', 'required' => false],
                ],
            ],
            [
                'name' => 'slides',
                'label' => 'Slides',
                'type' => 'repeater',
                'required' => false,
                'children' => [
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => false],
                ],
            ],
        ],
    ]);

    $group->load(['fields.children']);

    $address = $group->fields->firstWhere('name', 'address');
    $slides = $group->fields->firstWhere('name', 'slides');

    expect($address)->not->toBeNull()
        ->and($address->children)->toHaveCount(2)
        ->and($slides)->not->toBeNull()
        ->and($slides->children)->toHaveCount(1);
});

it('compiles group fields with nested subfield components', function (): void {
    $field = new FieldDefinition(
        name: 'address',
        label: 'Address',
        type: 'group',
        children: collect([
            new FieldDefinition(name: 'street', label: 'Street', type: 'text'),
            new FieldDefinition(name: 'city', label: 'City', type: 'text'),
        ]),
    );

    $component = app(FieldTypeRegistry::class)
        ->get('group')
        ->formComponent($field);

    expect($component)->toBeInstanceOf(Fieldset::class)
        ->and($component->getDefaultChildComponents())->toHaveCount(2);
});

it('compiles persisted group fields into runtime form components', function (): void {
    require_once __DIR__.'/../Support/TestItemResource.php';

    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $group = FieldGroup::query()->create([
        'name' => 'Grouped',
        'slug' => 'grouped',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Grouped',
        'slug' => 'grouped',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'gruop',
                'label' => 'Gruop',
                'type' => 'group',
                'required' => false,
                'children' => [
                    ['name' => 'street', 'label' => 'Street', 'type' => 'text', 'required' => false],
                    ['name' => 'city', 'label' => 'City', 'type' => 'text', 'required' => false],
                ],
            ],
        ],
    ]);

    $sections = TestItemResource::customFieldComponents();

    $fieldset = collect($sections[0]->getDefaultChildComponents())
        ->first(fn ($component) => $component instanceof Fieldset);

    expect($fieldset)->not->toBeNull()
        ->and($fieldset->getDefaultChildComponents())->toHaveCount(2);
});

it('persists group and repeater values as json', function (): void {
    $record = TestItem::query()->create(['title' => 'Layout test']);
    $manager = app(CustomFieldsManager::class);

    $groupField = new FieldDefinition(
        name: 'address',
        label: 'Address',
        type: 'group',
        children: collect([
            new FieldDefinition(name: 'street', label: 'Street', type: 'text'),
            new FieldDefinition(name: 'city', label: 'City', type: 'text'),
        ]),
    );

    $repeaterField = new FieldDefinition(
        name: 'slides',
        label: 'Slides',
        type: 'repeater',
        children: collect([
            new FieldDefinition(name: 'title', label: 'Title', type: 'text'),
        ]),
    );

    $manager->saveValues('item', $record, [
        'address' => [['street' => 'Main St', 'city' => 'Berlin']],
        'slides' => [
            ['title' => 'Slide 1'],
            ['title' => 'Slide 2'],
        ],
    ], collect([$groupField, $repeaterField]));

    $loaded = $manager->loadValues('item', $record, collect([$groupField, $repeaterField]));

    expect($loaded['address'])->toBe(['street' => 'Main St', 'city' => 'Berlin'])
        ->and($loaded['slides'])->toBe([
            ['title' => 'Slide 1'],
            ['title' => 'Slide 2'],
        ]);
});

it('applies repeater min and max items from config', function (): void {
    $field = new FieldDefinition(
        name: 'slides',
        label: 'Slides',
        type: 'repeater',
        config: ['min_items' => 1, 'max_items' => 5],
        children: collect([
            new FieldDefinition(name: 'title', label: 'Title', type: 'text'),
        ]),
    );

    $component = app(FieldTypeRegistry::class)
        ->get('repeater')
        ->formComponent($field);

    expect($component)->toBeInstanceOf(Repeater::class)
        ->and($component->getMinItems())->toBe(1)
        ->and($component->getMaxItems())->toBe(5);
});

it('does not apply item limits for optional repeaters without configured bounds', function (): void {
    $field = new FieldDefinition(
        name: 'slides',
        label: 'Slides',
        type: 'repeater',
        config: ['min_items' => 0, 'max_items' => 0],
        children: collect([
            new FieldDefinition(name: 'title', label: 'Title', type: 'text'),
        ]),
    );

    $component = app(FieldTypeRegistry::class)
        ->get('repeater')
        ->formComponent($field);

    expect($component->getMinItems())->toBeNull()
        ->and($component->getMaxItems())->toBeNull();
});

it('requires at least one repeater item when the field is required', function (): void {
    $field = new FieldDefinition(
        name: 'slides',
        label: 'Slides',
        type: 'repeater',
        validation: ['required' => true, 'rules' => []],
        children: collect([
            new FieldDefinition(name: 'title', label: 'Title', type: 'text'),
        ]),
    );

    $component = app(FieldTypeRegistry::class)
        ->get('repeater')
        ->formComponent($field);

    expect($component->getMinItems())->toBe(1);
});

it('builds field definitions with nested children from models', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Layout',
        'slug' => 'layout',
        'location_rules' => [],
        'active' => true,
    ]);

    $parent = $group->fields()->create([
        'name' => 'details',
        'label' => 'Details',
        'type' => 'group',
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    $parent->children()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'note',
        'label' => 'Note',
        'type' => 'text',
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    $group->load(['fields.children']);

    $definition = FieldGroupDefinition::fromModel($group);

    expect($definition->fields)->toHaveCount(1)
        ->and($definition->fields->first()->children)->toHaveCount(1)
        ->and($definition->fields->first()->children->first()->name)->toBe('note');
});
