<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/TestItemResource.php';

use Illuminate\Support\Facades\Cache;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Types\ButtonGroupFieldType;
use Moox\Builder\FieldTypes\Types\ColorFieldType;
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
});

it('persists tab nested color defaults when the form value is empty on create', function (): void {
    app(FieldGroupPersistence::class)->sync(
        FieldGroup::query()->create([
            'name' => 'Tabs',
            'slug' => 'tabs-color-default',
            'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
            'active' => true,
        ]),
        [
            'name' => 'Tabs',
            'slug' => 'tabs-color-default',
            'active' => true,
            'sort' => 0,
            'target_entities' => ['item'],
            'fields' => [
                [
                    'name' => 'tab-design',
                    'label' => 'Design',
                    'type' => 'tab',
                    'children' => [
                        [
                            'name' => 'lackfarbe',
                            'label' => 'Lackfarbe',
                            'type' => 'color',
                            'required' => false,
                            'config' => ['default' => 'ff0000'],
                        ],
                    ],
                ],
            ],
        ],
    );

    $record = TestItem::query()->create(['title' => 'Demo']);

    app(CustomFieldsManager::class)->saveFromFormData(
        TestItemResource::class,
        $record,
        ['lackfarbe' => null],
    );

    $stored = FieldValue::query()
        ->forRecord('item', $record->getKey())
        ->where('field_name', 'lackfarbe')
        ->first();

    expect($stored?->value_string)->toBe('#ff0000');
});

it('persists tab nested button group defaults when the form value is empty on create', function (): void {
    app(FieldGroupPersistence::class)->sync(
        FieldGroup::query()->create([
            'name' => 'Tabs',
            'slug' => 'tabs-button-default',
            'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
            'active' => true,
        ]),
        [
            'name' => 'Tabs',
            'slug' => 'tabs-button-default',
            'active' => true,
            'sort' => 0,
            'target_entities' => ['item'],
            'fields' => [
                [
                    'name' => 'tab-drive',
                    'label' => 'Antrieb',
                    'type' => 'tab',
                    'children' => [
                        [
                            'name' => 'antrieb',
                            'label' => 'Antrieb',
                            'type' => 'button_group',
                            'required' => false,
                            'config' => ['default' => 'awd'],
                            'options' => [
                                ['label' => 'Front', 'value' => 'fwd'],
                                ['label' => 'Allrad', 'value' => 'awd'],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    );

    $record = TestItem::query()->create(['title' => 'Demo']);

    app(CustomFieldsManager::class)->saveFromFormData(
        TestItemResource::class,
        $record,
        ['antrieb' => null],
    );

    $stored = FieldValue::query()
        ->forRecord('item', $record->getKey())
        ->where('field_name', 'antrieb')
        ->first();

    expect($stored?->value_string)->toBe('awd');
});

it('resolves tab nested defaults from cached definitions', function (): void {
    app(FieldGroupPersistence::class)->sync(
        FieldGroup::query()->create([
            'name' => 'Tabs',
            'slug' => 'tabs-resolve-default',
            'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
            'active' => true,
        ]),
        [
            'name' => 'Tabs',
            'slug' => 'tabs-resolve-default',
            'active' => true,
            'sort' => 0,
            'target_entities' => ['item'],
            'fields' => [
                [
                    'name' => 'tab-design',
                    'label' => 'Design',
                    'type' => 'tab',
                    'children' => [
                        [
                            'name' => 'lackfarbe',
                            'label' => 'Lackfarbe',
                            'type' => 'color',
                            'required' => false,
                            'config' => ['default' => 'ff0000'],
                        ],
                    ],
                ],
            ],
        ],
    );

    $field = app(DefinitionRegistry::class)
        ->fieldGroupsFor(TestItemResource::customFieldsLocationContext())
        ->firstWhere('slug', 'tabs-resolve-default')
        ?->fields
        ->firstWhere('name', 'tab-design')
        ?->children
        ->firstWhere('name', 'lackfarbe');

    expect($field)->not->toBeNull()
        ->and(app(DefaultValue::class)->resolveForField($field))->toBe('#ff0000');
});

it('applies defaults on tab nested color and button group components', function (): void {
    $colorField = new FieldDefinition(
        name: 'lackfarbe',
        label: 'Lackfarbe',
        type: 'color',
        config: ['default' => 'ff0000'],
    );

    $buttonField = new FieldDefinition(
        name: 'antrieb',
        label: 'Antrieb',
        type: 'button_group',
        config: ['default' => 'awd'],
        options: [
            ['label' => 'Front', 'value' => 'fwd'],
            ['label' => 'Allrad', 'value' => 'awd'],
        ],
    );

    expect((new ColorFieldType)->formComponent($colorField)->getDefaultState())->toBe('#ff0000')
        ->and((new ButtonGroupFieldType)->formComponent($buttonField)->getDefaultState())->toBe('awd')
        ->and(app(DefaultValue::class)->resolveForField($colorField))->toBe('#ff0000')
        ->and(app(DefaultValue::class)->resolveForField($buttonField))->toBe('awd');
});
