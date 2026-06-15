<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/TestItemResource.php';

uses(Moox\Builder\Tests\TestCase::class);

use Illuminate\Validation\ValidationException;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Services\FieldValuePurger;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\Support\TestItemResource;

beforeEach(function (): void {
    $this->createItemsTable();

    config()->set('builder.entities', [
        'item' => ['resource' => TestItemResource::class],
    ]);
});

it('purges values when a record is deleted', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);

    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => $record->getKey(),
        'field_name' => 'color',
        'value_string' => 'red',
    ]);

    app(\Moox\Builder\Support\EntityModelDeletionRegistrar::class)->register();

    $record->delete();

    expect(FieldValue::query()->count())->toBe(0);
});

it('purges values when a field is deleted', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Specs',
        'slug' => 'specs',
        'active' => true,
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
    ]);

    $field = Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'color',
        'label' => 'Color',
        'type' => 'text',
    ]);

    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => 1,
        'field_name' => 'color',
        'value_string' => 'red',
    ]);

    $field->delete();

    expect(FieldValue::query()->count())->toBe(0);
});

it('purges values when a field is renamed during sync', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Specs',
        'slug' => 'specs',
        'active' => true,
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
    ]);

    $field = Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'color',
        'label' => 'Color',
        'type' => 'text',
    ]);

    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => 1,
        'field_name' => 'color',
        'value_string' => 'red',
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Specs',
        'slug' => 'specs',
        'active' => true,
        'target_entities' => ['item'],
        'fields' => [[
            'id' => $field->getKey(),
            'name' => 'farbe',
            'label' => 'Farbe',
            'type' => 'text',
        ]],
    ]);

    expect(FieldValue::query()->where('field_name', 'color')->count())->toBe(0);
});

it('rejects duplicate field names across groups for the same entity', function (): void {
    FieldGroup::query()->create([
        'name' => 'Existing',
        'slug' => 'existing',
        'active' => true,
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
    ])->fields()->create([
        'name' => 'vin',
        'label' => 'VIN',
        'type' => 'text',
    ]);

    $group = new FieldGroup;

    expect(fn () => app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'New',
        'slug' => 'new',
        'active' => true,
        'target_entities' => ['item'],
        'fields' => [[
            'name' => 'vin',
            'label' => 'VIN duplicate',
            'type' => 'text',
        ]],
    ]))->toThrow(ValidationException::class);
});

it('hashes password values on save', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);
    $driver = app(\Moox\Builder\Storage\TypedValueDriver::class);

    $driver->save('item', $record, [
        'secret' => 'plain-text',
    ], collect([
        new \Moox\Builder\Data\FieldDefinition('secret', 'Secret', 'password'),
    ]));

    $stored = FieldValue::query()->forRecord('item', $record->getKey())->first();

    expect($stored?->value_string)->not->toBe('plain-text')
        ->and(password_verify('plain-text', (string) $stored?->value_string))->toBeTrue();
});

it('does not load password values back into forms', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);

    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => $record->getKey(),
        'field_name' => 'secret',
        'value_string' => bcrypt('hidden'),
    ]);

    $loaded = app(\Moox\Builder\Storage\TypedValueDriver::class)->load(
        'item',
        $record,
        collect([new \Moox\Builder\Data\FieldDefinition('secret', 'Secret', 'password')]),
    );

    expect($loaded)->toBe([]);
});

it('purges values for a record via purger service', function (): void {
    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => 99,
        'field_name' => 'color',
        'value_string' => 'red',
    ]);

    app(FieldValuePurger::class)->purgeForRecord('item', 99);

    expect(FieldValue::query()->count())->toBe(0);
});
