<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItemResource.php';

use Filament\Forms\Components\Repeater;
use Illuminate\Support\Facades\Cache;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Capabilities\RepeaterItems;
use Moox\Builder\FieldTypes\Types\RepeaterFieldType;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Tests\Support\TestItemResource;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

it('builds default row data for repeater subfields including color', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'variants',
        label: 'Variants',
        type: 'repeater',
        children: collect([
            new FieldDefinition(
                name: 'lackfarbe',
                label: 'Paint color',
                type: 'color',
                config: ['default' => 'ff0000'],
            ),
            new FieldDefinition(
                name: 'label',
                label: 'Label',
                type: 'text',
                config: ['default' => 'Standard'],
            ),
        ]),
    );

    expect($capability->defaultDataForChildren($field->children))->toBe([
        'lackfarbe' => '#ff0000',
        'label' => 'Standard',
    ]);
});

it('merges missing color defaults into stored repeater rows', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'variants',
        label: 'Variants',
        type: 'repeater',
        children: collect([
            new FieldDefinition(
                name: 'lackfarbe',
                label: 'Paint color',
                type: 'color',
                config: ['default' => '1a1a1a'],
            ),
        ]),
    );

    $merged = $capability->mergeCompoundDefaults($field, [
        ['lackfarbe' => ''],
        ['label' => 'Custom'],
    ]);

    expect($merged)->toBe([
        ['lackfarbe' => '#1a1a1a'],
        ['label' => 'Custom', 'lackfarbe' => '#1a1a1a'],
    ]);
});

it('applies color defaults on nested repeater color pickers', function (): void {
    $field = new FieldDefinition(
        name: 'variants',
        label: 'Variants',
        type: 'repeater',
        children: collect([
            new FieldDefinition(
                name: 'lackfarbe',
                label: 'Paint color',
                type: 'color',
                config: ['default' => '336699'],
            ),
        ]),
    );

    $component = (new RepeaterFieldType)->formComponent($field);
    $schema = $component->getDefaultChildComponents();
    $colorField = collect($schema)->first(fn ($item) => $item->getName() === 'lackfarbe');

    expect($colorField)->not->toBeNull()
        ->and($colorField->getDefaultState())->toBe('#336699');
});

it('ignores legacy zero item limits for optional repeaters', function (): void {
    $capability = app(RepeaterItems::class);

    $field = new FieldDefinition(
        name: 'variants',
        label: 'Variants',
        type: 'repeater',
        config: ['min_items' => 0, 'max_items' => 0],
    );

    $component = Repeater::make('variants');
    $result = $capability->apply($component, $field);

    expect($result->getMinItems())->toBeNull()
        ->and($result->getMaxItems())->toBeNull();
});

it('ignores stale range bounds stored in legacy config keys', function (): void {
    $capability = app(RepeaterItems::class);

    $field = new FieldDefinition(
        name: 'variants',
        label: 'Variants',
        type: 'repeater',
        config: ['min' => 50, 'max' => 500, 'step' => 5],
    );

    $component = Repeater::make('variants');
    $result = $capability->apply($component, $field);

    expect($result->getMinItems())->toBeNull()
        ->and($result->getMaxItems())->toBeNull();
});

it('persists default values configured on repeater subfields', function (): void {
    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $group = FieldGroup::query()->create([
        'name' => 'Repeater defaults',
        'slug' => 'repeater-defaults',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Repeater defaults',
        'slug' => 'repeater-defaults',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'variants',
                'label' => 'Variants',
                'type' => 'repeater',
                'required' => false,
                'children' => [
                    [
                        'name' => 'lackfarbe',
                        'label' => 'Paint color',
                        'type' => 'color',
                        'required' => false,
                        'config' => ['default' => 'ff5500'],
                    ],
                ],
            ],
        ],
    ]);

    $color = app(DefinitionRegistry::class)
        ->fieldGroupsFor(TestItemResource::customFieldsLocationContext())
        ->first()
        ?->fields
        ->firstWhere('name', 'variants')
        ?->children
        ->firstWhere('name', 'lackfarbe');

    expect($color)->not->toBeNull()
        ->and($color->config['default'] ?? null)->toBe('ff5500')
        ->and(app(DefaultValue::class)->resolveForField($color))->toBe('#ff5500');
});
