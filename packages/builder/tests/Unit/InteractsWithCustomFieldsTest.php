<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/TestItemResource.php';

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Moox\Builder\FieldTypes\Value\StoredPassword;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->createItemsTable();

    FieldGroup::query()->delete();

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    $group = FieldGroup::query()->create([
        'name' => 'Vehicle data',
        'slug' => 'vehicle-data',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'fahrzeugtyp-modell',
        'label' => 'Model',
        'type' => 'text',
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'farbe',
        'label' => 'Farbe',
        'type' => 'text',
        'sort' => 1,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'unfallfrei',
        'label' => 'Unfallfrei',
        'type' => 'toggle',
        'sort' => 2,
        'config' => ['default' => true],
        'validation' => ['required' => false, 'rules' => []],
    ]);
});

it('loads builder values through the model trait', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);

    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => $record->getKey(),
        'field_name' => 'fahrzeugtyp-modell',
        'value_string' => 'Golf GTI',
    ]);

    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => $record->getKey(),
        'field_name' => 'unfallfrei',
        'value_boolean' => true,
    ]);

    expect($record->customField('fahrzeugtyp-modell'))->toBe('Golf GTI')
        ->and($record->customField('unfallfrei'))->toBeTrue()
        ->and($record->hasCustomField('fahrzeugtyp-modell'))->toBeTrue()
        ->and($record->hasCustomField('missing'))->toBeFalse()
        ->and($record->customFields())->toMatchArray([
            'fahrzeugtyp-modell' => 'Golf GTI',
            'unfallfrei' => true,
        ]);
});

it('merges configured defaults when no stored value exists', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);

    expect($record->customField('unfallfrei'))->toBeTrue()
        ->and($record->hasCustomField('unfallfrei'))->toBeTrue();
});

it('exposes valid builder field names as native model attributes', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);

    $record->setCustomField('farbe', 'Blau');

    expect($record->farbe)->toBe('Blau')
        ->and($record->getAttribute('farbe'))->toBe('Blau')
        ->and($record->title)->toBe('Demo');
});

it('merges builder values into model arrays for api style serialization', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);
    $record->setCustomField('farbe', 'Rot');

    expect($record->toArray())->toMatchArray([
        'title' => 'Demo',
        'farbe' => 'Rot',
    ]);
});

it('saves builder values through the model trait', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);

    $record->setCustomFields([
        'fahrzeugtyp-modell' => 'Polo GTI',
        'unfallfrei' => false,
    ]);

    $stored = FieldValue::query()
        ->forRecord('item', $record->getKey())
        ->get()
        ->keyBy('field_name');

    expect($stored['fahrzeugtyp-modell']->value_string)->toBe('Polo GTI')
        ->and($stored['unfallfrei']->value_boolean)->toBeFalse()
        ->and($record->fresh()->customField('fahrzeugtyp-modell'))->toBe('Polo GTI')
        ->and($record->fresh()->customField('unfallfrei'))->toBeFalse();
});

it('updates a single builder field through the model trait', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);

    $record->setCustomField('fahrzeugtyp-modell', 'Golf R');

    expect($record->fresh()->customField('fahrzeugtyp-modell'))->toBe('Golf R');
});

it('clears a builder field through the model trait', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);
    $record->setCustomField('farbe', 'Blau');

    $record->clearCustomField('farbe');

    expect(FieldValue::query()->forRecord('item', $record->getKey())->where('field_name', 'farbe')->exists())->toBeFalse()
        ->and($record->fresh()->customField('farbe', 'missing'))->toBe('missing');
});

it('rejects unknown builder fields when saving through the model trait', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);

    expect(fn () => $record->setCustomField('unknown-field', 'value'))
        ->toThrow(InvalidArgumentException::class);
});

it('resolves the builder entity key from the model', function (): void {
    expect(TestItem::resolveCustomFieldsEntity())->toBe('item');
});

it('lists builder field definitions for the entity', function (): void {
    expect(TestItem::customFieldNames())->toContain('farbe', 'unfallfrei', 'fahrzeugtyp-modell');
});

it('queries records by builder field values using normal where syntax', function (): void {
    $blue = TestItem::query()->create(['title' => 'Blue car']);
    $red = TestItem::query()->create(['title' => 'Red car']);

    $blue->setCustomField('farbe', 'Blau');
    $red->setCustomField('farbe', 'Rot');

    $results = TestItem::query()
        ->where('farbe', 'Blau')
        ->pluck('title')
        ->all();

    expect($results)->toBe(['Blue car']);
});

it('queries native database columns normally even when a builder field shares the name', function (): void {
    $group = FieldGroup::query()->first();

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'title',
        'label' => 'Shadow title',
        'type' => 'text',
        'sort' => 3,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    $record = TestItem::query()->create(['title' => 'Native title']);
    $record->setCustomField('title', 'Builder title');

    $results = TestItem::query()
        ->where('title', 'Native title')
        ->pluck('id')
        ->all();

    expect($results)->toBe([$record->getKey()]);
});

it('eager loads builder values for a collection with one query', function (): void {
    $first = TestItem::query()->create(['title' => 'First']);
    $second = TestItem::query()->create(['title' => 'Second']);

    $first->setCustomField('farbe', 'Blau');
    $second->setCustomField('farbe', 'Rot');

    $records = TestItem::query()->withCustomFields()->orderBy('id')->get();

    DB::enableQueryLog();

    expect($records[0]->farbe)->toBe('Blau')
        ->and($records[1]->farbe)->toBe('Rot');

    $valueQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'builder_field_values'));

    expect($valueQueries)->toHaveCount(0);
});

it('prefers native model attributes over builder fields with the same name', function (): void {
    $group = FieldGroup::query()->first();
    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'title',
        'label' => 'Shadow title',
        'type' => 'text',
        'sort' => 3,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    $record = TestItem::query()->create(['title' => 'Native title']);
    $record->setCustomField('title', 'Builder title');

    expect($record->title)->toBe('Native title');
});

it('masks password values in debug output', function (): void {
    $group = FieldGroup::query()->first();

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'secret',
        'label' => 'Secret',
        'type' => 'password',
        'sort' => 4,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    $record = TestItem::query()->create(['title' => 'Demo']);
    $record->setCustomField('secret', 'top-secret');

    expect($record->__debugInfo()['secret'])->toBe('••••••••')
        ->and($record->customField('secret'))->toBe(StoredPassword::instance());
});
