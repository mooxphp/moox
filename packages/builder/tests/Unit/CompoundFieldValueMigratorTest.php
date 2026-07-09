<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';

use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Services\CompoundFieldValueMigrator;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Support\TypedValueColumns;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->createItemsTable();
});

function seedGroupValue(string $fieldName, string $type, mixed $value): void
{
    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => 1,
        ...TypedValueColumns::attributesFor($type, $value),
        'field_name' => $fieldName,
    ]);
}

it('renames nested group subfield keys inside stored json', function (): void {
    TestItem::query()->create(['id' => 1, 'title' => 'Demo']);

    $group = FieldGroup::query()->create([
        'name' => 'Group',
        'slug' => 'group-rename',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Group',
        'slug' => 'group-rename',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'standort',
                'label' => 'Standort',
                'type' => 'group',
                'children' => [
                    ['id' => null, 'name' => 'stadt', 'label' => 'Stadt', 'type' => 'text', 'required' => false],
                ],
            ],
        ],
    ]);

    $standort = Field::query()
        ->where('field_group_id', $group->getKey())
        ->whereNull('parent_field_id')
        ->where('name', 'standort')
        ->first();

    $stadt = Field::query()
        ->where('parent_field_id', $standort?->getKey())
        ->where('name', 'stadt')
        ->first();

    $stadtId = $stadt->getKey();

    seedGroupValue('standort', 'group', ['stadt' => 'Berlin', 'plz' => '10115']);

    app(FieldGroupPersistence::class)->sync($group->fresh(), [
        'name' => 'Group',
        'slug' => 'group-rename',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'id' => $standort->getKey(),
                'name' => 'standort',
                'label' => 'Standort',
                'type' => 'group',
                'children' => [
                    ['id' => $stadtId, 'name' => 'city', 'label' => 'City', 'type' => 'text', 'required' => false],
                    ['name' => 'plz', 'label' => 'PLZ', 'type' => 'text', 'required' => false],
                ],
            ],
        ],
    ]);

    $stored = FieldValue::query()->where('field_name', 'standort')->first();

    expect($stored?->value_json)->toMatchArray([
        'city' => 'Berlin',
        'plz' => '10115',
    ]);
});

it('removes nested group subfield keys when deleted from the schema', function (): void {
    TestItem::query()->create(['id' => 1, 'title' => 'Demo']);

    $group = FieldGroup::query()->create([
        'name' => 'Group',
        'slug' => 'group-delete',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Group',
        'slug' => 'group-delete',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'standort',
                'label' => 'Standort',
                'type' => 'group',
                'children' => [
                    ['name' => 'stadt', 'label' => 'Stadt', 'type' => 'text', 'required' => false],
                    ['name' => 'plz', 'label' => 'PLZ', 'type' => 'text', 'required' => false],
                ],
            ],
        ],
    ]);

    seedGroupValue('standort', 'group', ['stadt' => 'Berlin', 'plz' => '10115']);

    $standortId = Field::query()
        ->where('field_group_id', $group->getKey())
        ->whereNull('parent_field_id')
        ->where('name', 'standort')
        ->value('id');

    $stadtId = Field::query()
        ->where('parent_field_id', $standortId)
        ->where('name', 'stadt')
        ->value('id');

    app(FieldGroupPersistence::class)->sync($group->fresh(), [
        'name' => 'Group',
        'slug' => 'group-delete',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'id' => $standortId,
                'name' => 'standort',
                'label' => 'Standort',
                'type' => 'group',
                'children' => [
                    ['id' => $stadtId, 'name' => 'stadt', 'label' => 'Stadt', 'type' => 'text', 'required' => false],
                ],
            ],
        ],
    ]);

    expect(FieldValue::query()->where('field_name', 'standort')->value('value_json'))
        ->toBe(['stadt' => 'Berlin']);
});

it('renames nested repeater subfield keys inside stored json', function (): void {
    TestItem::query()->create(['id' => 1, 'title' => 'Demo']);

    $group = FieldGroup::query()->create([
        'name' => 'Repeater',
        'slug' => 'repeater-rename',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Repeater',
        'slug' => 'repeater-rename',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'ausstattung',
                'label' => 'Ausstattung',
                'type' => 'repeater',
                'children' => [
                    ['name' => 'merkmal', 'label' => 'Merkmal', 'type' => 'text', 'required' => false],
                ],
            ],
        ],
    ]);

    $repeater = Field::query()
        ->where('field_group_id', $group->getKey())
        ->whereNull('parent_field_id')
        ->where('name', 'ausstattung')
        ->first();

    $merkmalId = Field::query()
        ->where('parent_field_id', $repeater?->getKey())
        ->where('name', 'merkmal')
        ->value('id');

    $repeaterId = $repeater->getKey();

    seedGroupValue('ausstattung', 'repeater', [
        ['merkmal' => 'Sitzheizung'],
        ['merkmal' => 'Navi'],
    ]);

    app(FieldGroupPersistence::class)->sync($group->fresh(), [
        'name' => 'Repeater',
        'slug' => 'repeater-rename',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'id' => $repeaterId,
                'name' => 'ausstattung',
                'label' => 'Ausstattung',
                'type' => 'repeater',
                'children' => [
                    ['id' => $merkmalId, 'name' => 'feature', 'label' => 'Feature', 'type' => 'text', 'required' => false],
                ],
            ],
        ],
    ]);

    expect(FieldValue::query()->where('field_name', 'ausstattung')->value('value_json'))->toBe([
        ['feature' => 'Sitzheizung'],
        ['feature' => 'Navi'],
    ]);
});

it('renames nested flexible content subfield keys inside stored json', function (): void {
    TestItem::query()->create(['id' => 1, 'title' => 'Demo']);

    $group = FieldGroup::query()->create([
        'name' => 'Flexible',
        'slug' => 'flex-rename',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Flexible',
        'slug' => 'flex-rename',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'content',
                'label' => 'Content',
                'type' => 'flexible_content',
                'layouts' => [
                    [
                        'name' => 'hero',
                        'label' => 'Hero',
                        'children' => [
                            ['name' => 'titel', 'label' => 'Titel', 'type' => 'text', 'required' => false],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $content = Field::query()
        ->where('field_group_id', $group->getKey())
        ->whereNull('parent_field_id')
        ->where('name', 'content')
        ->first();

    $hero = Field::query()
        ->where('parent_field_id', $content?->getKey())
        ->where('name', 'hero')
        ->first();

    $contentId = $content->getKey();
    $titelId = Field::query()
        ->where('parent_field_id', $hero?->getKey())
        ->where('name', 'titel')
        ->value('id');

    seedGroupValue('content', 'flexible_content', [
        ['type' => 'hero', 'data' => ['titel' => 'Hello']],
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Flexible',
        'slug' => 'flex-rename',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'id' => $contentId,
                'name' => 'content',
                'label' => 'Content',
                'type' => 'flexible_content',
                'layouts' => [
                    [
                        'id' => $hero?->getKey(),
                        'name' => 'hero',
                        'label' => 'Hero',
                        'children' => [
                            ['id' => $titelId, 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => false],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    expect(FieldValue::query()->where('field_name', 'content')->value('value_json'))->toBe([
        ['type' => 'hero', 'data' => ['title' => 'Hello']],
    ]);
});

it('still purges root field rows when a top level field is renamed', function (): void {
    TestItem::query()->create(['id' => 1, 'title' => 'Demo']);

    $group = FieldGroup::query()->create([
        'name' => 'Root',
        'slug' => 'root-rename',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Root',
        'slug' => 'root-rename',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            ['name' => 'preis', 'label' => 'Preis', 'type' => 'number', 'required' => false],
        ],
    ]);

    seedGroupValue('preis', 'number', 1000);

    $fieldId = Field::query()
        ->where('field_group_id', $group->getKey())
        ->whereNull('parent_field_id')
        ->where('name', 'preis')
        ->value('id');

    app(FieldGroupPersistence::class)->sync($group->fresh(), [
        'name' => 'Root',
        'slug' => 'root-rename',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            ['id' => $fieldId, 'name' => 'price', 'label' => 'Price', 'type' => 'number', 'required' => false],
        ],
    ]);

    expect(FieldValue::query()->where('field_name', 'preis')->exists())->toBeFalse()
        ->and(FieldValue::query()->where('field_name', 'price')->exists())->toBeFalse();
});

it('migrates nested subfields directly through the migrator service', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Direct',
        'slug' => 'direct',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    $parent = $group->fields()->create([
        'name' => 'standort',
        'label' => 'Standort',
        'type' => 'group',
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    $child = $group->fields()->create([
        'parent_field_id' => $parent->getKey(),
        'name' => 'stadt',
        'label' => 'Stadt',
        'type' => 'text',
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    seedGroupValue('standort', 'group', ['stadt' => 'Berlin']);

    $child->name = 'city';
    $child->save();

    app(CompoundFieldValueMigrator::class)->renameNestedSubfield($child->fresh(), 'stadt', ['item']);

    expect(FieldValue::query()->where('field_name', 'standort')->value('value_json'))
        ->toBe(['city' => 'Berlin']);
});
