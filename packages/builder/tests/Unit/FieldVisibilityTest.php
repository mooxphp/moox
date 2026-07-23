<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/TestItemResource.php';

use Illuminate\Support\Facades\Cache;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Data\FieldGroupDefinition;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Support\FieldVisibility;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\Support\TestItemResource;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

it('treats fields and groups as visible in every context by default', function (): void {
    $field = FieldDefinition::fromArray(['name' => 'a', 'label' => 'A', 'type' => 'text']);
    $group = FieldGroupDefinition::fromArray(['name' => 'G', 'slug' => 'g', 'placement' => 'default', 'fields' => []]);

    foreach (FieldVisibility::CONTEXTS as $context) {
        expect($field->isVisibleIn($context))->toBeTrue()
            ->and($group->isVisibleIn($context))->toBeTrue();
    }
});

it('removes groups hidden in the requested context but keeps them elsewhere', function (): void {
    $groups = collect([
        FieldGroupDefinition::fromArray([
            'name' => 'Visible', 'slug' => 'v', 'placement' => 'default',
            'fields' => [['name' => 'a', 'label' => 'A', 'type' => 'text']],
        ]),
        FieldGroupDefinition::fromArray([
            'name' => 'Hidden', 'slug' => 'h', 'placement' => 'default',
            'settings' => ['visible_admin' => false],
            'fields' => [['name' => 'b', 'label' => 'B', 'type' => 'text']],
        ]),
    ]);

    expect(FieldVisibility::filterGroups($groups, FieldVisibility::ADMIN)->pluck('slug')->all())->toBe(['v'])
        ->and(FieldVisibility::filterGroups($groups, FieldVisibility::API)->pluck('slug')->all())->toBe(['v', 'h']);
});

it('removes individual fields hidden in the requested context, cascading into children', function (): void {
    $group = FieldGroupDefinition::fromArray([
        'name' => 'G', 'slug' => 'g', 'placement' => 'default',
        'fields' => [
            ['name' => 'public', 'label' => 'Public', 'type' => 'text'],
            ['name' => 'secret', 'label' => 'Secret', 'type' => 'text', 'settings' => ['visible_api' => false]],
            ['name' => 'wrapper', 'label' => 'Wrapper', 'type' => 'repeater', 'children' => [
                ['name' => 'child-visible', 'label' => 'CV', 'type' => 'text'],
                ['name' => 'child-hidden', 'label' => 'CH', 'type' => 'text', 'settings' => ['visible_api' => false]],
            ]],
        ],
    ]);

    $filtered = FieldVisibility::filterFields($group->fields, FieldVisibility::API);

    expect($filtered->pluck('name')->all())->toBe(['public', 'wrapper'])
        ->and($filtered->firstWhere('name', 'wrapper')->children->pluck('name')->all())->toBe(['child-visible']);
});

it('round trips per-context visibility through field group persistence', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Visibility',
        'slug' => 'visibility-roundtrip',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    $persistence = app(FieldGroupPersistence::class);

    $persistence->sync($group, [
        'name' => 'Visibility',
        'slug' => 'visibility-roundtrip',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'secret',
                'label' => 'Secret',
                'type' => 'text',
                'required' => false,
                'settings' => [
                    'visible_admin' => true,
                    'visible_frontend' => false,
                    'visible_api' => false,
                ],
            ],
        ],
    ]);

    $stored = $group->fields()->where('name', 'secret')->first();

    expect($stored->settings)->toMatchArray([
        'visible_admin' => true,
        'visible_frontend' => false,
        'visible_api' => false,
    ]);

    $rows = $persistence->fieldRowsForForm($group->fresh());

    expect($rows[0]['settings'])->toMatchArray([
        'visible_admin' => true,
        'visible_frontend' => false,
        'visible_api' => false,
    ]);
});

it('filters entity fields per context via the custom fields manager', function (): void {
    FieldGroup::query()->delete();
    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $group = FieldGroup::query()->create([
        'name' => 'Public group',
        'slug' => 'public-group',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'public',
        'label' => 'Public',
        'type' => 'text',
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'admin-only',
        'label' => 'Admin only',
        'type' => 'text',
        'sort' => 1,
        'settings' => ['visible_api' => false],
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'api-only',
        'label' => 'API only',
        'type' => 'text',
        'sort' => 2,
        'settings' => ['visible_admin' => false],
        'validation' => ['required' => false, 'rules' => []],
    ]);

    $internal = FieldGroup::query()->create([
        'name' => 'Internal group',
        'slug' => 'internal-group',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'settings' => ['visible_api' => false],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $internal->getKey(),
        'name' => 'note',
        'label' => 'Note',
        'type' => 'text',
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $manager = app(CustomFieldsManager::class);

    $adminNames = $manager->visibleFieldsForEntity('item', FieldVisibility::ADMIN)->pluck('name')->all();
    $apiNames = $manager->visibleFieldsForEntity('item', FieldVisibility::API)->pluck('name')->all();

    expect($adminNames)->toContain('public', 'admin-only', 'note')
        ->and($adminNames)->not->toContain('api-only')
        ->and($apiNames)->toContain('public', 'api-only')
        ->and($apiNames)->not->toContain('admin-only')
        ->and($apiNames)->not->toContain('note');
});

it('preserves context-hidden nested keys from existing storage when merging form input', function (): void {
    $group = FieldDefinition::fromArray([
        'name' => 'address',
        'label' => 'Address',
        'type' => 'group',
        'children' => [
            ['name' => 'city', 'label' => 'City', 'type' => 'text'],
            [
                'name' => 'internal_note',
                'label' => 'Internal note',
                'type' => 'text',
                'settings' => ['visible_admin' => false],
            ],
        ],
    ]);

    $merged = FieldVisibility::mergePreservingHidden(
        $group,
        [
            'city' => 'Berlin',
            'internal_note' => 'crafted-secret',
        ],
        [
            'city' => 'Munich',
            'internal_note' => 'stored-secret',
        ],
        FieldVisibility::ADMIN,
    );

    expect($merged)->toBe([
        'city' => 'Berlin',
        'internal_note' => 'stored-secret',
    ]);
});

it('preserves admin-hidden nested values across form saves', function (): void {
    FieldGroup::query()->delete();
    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $group = FieldGroup::query()->create([
        'name' => 'Address group',
        'slug' => 'address-group',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Address group',
        'slug' => 'address-group',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'address',
                'label' => 'Address',
                'type' => 'group',
                'children' => [
                    [
                        'name' => 'city',
                        'label' => 'City',
                        'type' => 'text',
                        'required' => false,
                    ],
                    [
                        'name' => 'internal_note',
                        'label' => 'Internal note',
                        'type' => 'text',
                        'required' => false,
                        'settings' => ['visible_admin' => false],
                    ],
                ],
            ],
        ],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $this->createItemsTable();
    $record = TestItem::query()->create(['title' => 'Demo']);
    $manager = app(CustomFieldsManager::class);
    $fields = $manager->fieldsForEntity('item');

    $manager->saveValues('item', $record, [
        'address' => [
            'city' => 'Munich',
            'internal_note' => 'stored-secret',
        ],
    ], $fields);

    $manager->saveFromFormData(
        TestItemResource::class,
        $record,
        [
            'address' => [
                'city' => 'Berlin',
                'internal_note' => 'crafted-secret',
            ],
        ],
    );

    $stored = FieldValue::query()
        ->forRecord('item', $record->getKey())
        ->where('field_name', 'address')
        ->value('value_json');

    expect($stored)->toMatchArray([
        'city' => 'Berlin',
        'internal_note' => 'stored-secret',
    ]);
});

it('preserves admin-hidden nested values when the form omits the key', function (): void {
    $group = FieldDefinition::fromArray([
        'name' => 'address',
        'label' => 'Address',
        'type' => 'group',
        'children' => [
            ['name' => 'city', 'label' => 'City', 'type' => 'text'],
            [
                'name' => 'internal_note',
                'label' => 'Internal note',
                'type' => 'text',
                'settings' => ['visible_admin' => false],
            ],
        ],
    ]);

    $merged = FieldVisibility::mergePreservingHidden(
        $group,
        ['city' => 'Berlin'],
        [
            'city' => 'Munich',
            'internal_note' => 'stored-secret',
        ],
        FieldVisibility::ADMIN,
    );

    expect($merged)->toBe([
        'city' => 'Berlin',
        'internal_note' => 'stored-secret',
    ]);
});
