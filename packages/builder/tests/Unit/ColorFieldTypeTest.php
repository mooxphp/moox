<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';

use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Types\ColorFieldType;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

it('applies normalized color defaults on runtime components', function (): void {
    $field = new FieldDefinition(
        name: 'lackfarbe',
        label: 'Lackfarbe',
        type: 'color',
        config: ['default' => 'ff0000'],
    );

    $component = (new ColorFieldType)->formComponent($field);

    expect($component->getDefaultState())->toBe('#ff0000')
        ->and($component->isLive())->toBeTrue();
});

it('syncs color default values through field group persistence', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Colors',
        'slug' => 'colors',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Colors',
        'slug' => 'colors',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'lackfarbe',
                'label' => 'Lackfarbe',
                'type' => 'color',
                'required' => false,
                'config' => ['default' => 'ff0000'],
            ],
        ],
    ]);

    expect($group->fields()->where('name', 'lackfarbe')->value('config'))->toMatchArray([
        'default' => 'ff0000',
    ])
        ->and(app(DefaultValue::class)->resolveForField(new FieldDefinition(
            name: 'lackfarbe',
            label: 'Lackfarbe',
            type: 'color',
            config: ['default' => 'ff0000'],
        )))->toBe('#ff0000');
});
