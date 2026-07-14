<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItemResource.php';

use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Registry\EntityRegistry;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Tests\Support\TestItemResource;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

it('converts target entities to location rules and back', function (): void {
    $persistence = app(FieldGroupPersistence::class);

    $rules = $persistence->locationRulesFromEntities(['item', 'product']);

    expect($rules)->toHaveCount(2)
        ->and($rules[0][0])->toMatchArray(['param' => 'entity', 'operator' => '==', 'value' => 'item'])
        ->and($rules[1][0])->toMatchArray(['param' => 'entity', 'operator' => '==', 'value' => 'product'])
        ->and($persistence->entitiesFromLocationRules($rules))->toBe(['item', 'product']);
});

it('prefers target entities when resolving location rules', function (): void {
    $persistence = app(FieldGroupPersistence::class);

    $rules = $persistence->resolveLocationRules([
        'target_entities' => ['item'],
        'location_rules' => [
            ['param' => 'entity', 'operator' => '==', 'value' => 'ignored'],
        ],
    ]);

    expect($rules)->toHaveCount(1)
        ->and($rules[0][0]['value'])->toBe('item');
});

it('resolves empty target entities to no location rules', function (): void {
    $persistence = app(FieldGroupPersistence::class);

    expect($persistence->resolveLocationRules(['target_entities' => []]))->toBe([])
        ->and($persistence->resolveLocationRules(['target_entities' => null]))->toBe([]);
});

it('updates existing fields by name when the form row id is missing', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Sync',
        'slug' => 'sync-by-name',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    $persistence = app(FieldGroupPersistence::class);

    $persistence->sync($group, [
        'name' => 'Sync',
        'slug' => 'sync-by-name',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'checkbox-list',
                'label' => 'Checkbox List',
                'type' => 'checkbox_list',
                'required' => false,
                'config' => ['default' => ['one']],
                'options' => [
                    ['label' => 'One', 'value' => 'one'],
                    ['label' => 'Two', 'value' => 'two'],
                ],
            ],
        ],
    ]);

    $fieldId = $group->fields()->where('name', 'checkbox-list')->value('id');

    $persistence->sync($group->fresh(), [
        'name' => 'Sync',
        'slug' => 'sync-by-name',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'checkbox-list',
                'label' => 'Checkbox List',
                'type' => 'checkbox_list',
                'required' => false,
                'config' => ['default' => ['one', 'two'], 'helperText' => 'Checkbox Helper'],
                'options' => [
                    ['label' => 'One', 'value' => 'one'],
                    ['label' => 'Two', 'value' => 'two'],
                ],
            ],
        ],
    ]);

    expect($group->fields()->where('name', 'checkbox-list')->count())->toBe(1)
        ->and($group->fields()->where('name', 'checkbox-list')->value('id'))->toBe($fieldId)
        ->and($group->fields()->where('name', 'checkbox-list')->value('config'))->toMatchArray([
            'default' => ['one', 'two'],
            'helperText' => 'Checkbox Helper',
        ]);
});

it('drops config keys that do not belong to the saved field type', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Sync',
        'slug' => 'sync-config-filter',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    $persistence = app(FieldGroupPersistence::class);

    $persistence->sync($group, [
        'name' => 'Sync',
        'slug' => 'sync-config-filter',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'variants',
                'label' => 'Variants',
                'type' => 'repeater',
                'required' => false,
                'config' => [
                    'min' => 50,
                    'max' => 500,
                    'step' => 5,
                    'default' => 245,
                ],
                'children' => [
                    [
                        'name' => 'title',
                        'label' => 'Title',
                        'type' => 'text',
                        'required' => false,
                    ],
                ],
            ],
        ],
    ]);

    expect($group->fields()->where('name', 'variants')->value('config'))->toBe([]);
});

it('only persists relation targets that are relatable resources', function (): void {
    $registry = new class extends EntityRegistry
    {
        public function relatableResources(): array
        {
            return ['item' => TestItemResource::class];
        }
    };
    app()->instance(EntityRegistry::class, $registry);

    $group = FieldGroup::query()->create([
        'name' => 'Relations',
        'slug' => 'relation-whitelist',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Relations',
        'slug' => 'relation-whitelist',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'valid-relation',
                'label' => 'Valid relation',
                'type' => 'relation',
                'required' => false,
                'config' => ['related_entity' => 'item', 'multiple' => false],
            ],
            [
                'name' => 'forged-relation',
                'label' => 'Forged relation',
                'type' => 'relation',
                'required' => false,
                'config' => ['related_entity' => 'user', 'multiple' => false],
            ],
        ],
    ]);

    expect($group->fields()->where('name', 'valid-relation')->value('config'))
        ->toMatchArray(['related_entity' => 'item'])
        ->and($group->fields()->where('name', 'forged-relation')->value('config'))
        ->not->toHaveKey('related_entity');
});

it('round trips conditional logic settings through field group persistence', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Conditional',
        'slug' => 'conditional-settings',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    $persistence = app(FieldGroupPersistence::class);

    $persistence->sync($group, [
        'name' => 'Conditional',
        'slug' => 'conditional-settings',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'customer_type',
                'label' => 'Customer type',
                'type' => 'text',
                'required' => false,
            ],
            [
                'name' => 'company',
                'label' => 'Company',
                'type' => 'text',
                'required' => true,
                'settings' => [
                    'conditions' => [
                        'enabled' => true,
                        'action' => 'show',
                        'logic' => 'or',
                        'rules' => [
                            ['field' => 'customer_type', 'operator' => 'equals', 'value' => 'business'],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $rows = $persistence->fieldRowsForForm($group->fresh());
    $company = collect($rows)->firstWhere('name', 'company');

    expect($company['settings']['conditions'] ?? null)->toMatchArray([
        'enabled' => true,
        'action' => 'show',
        'logic' => 'or',
        'rules' => [
            ['field' => 'customer_type', 'operator' => 'equals', 'value' => 'business'],
        ],
    ]);
});
