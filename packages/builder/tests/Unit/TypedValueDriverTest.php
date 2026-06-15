<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/TestItemResource.php';

uses(Moox\Builder\Tests\TestCase::class);

use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Storage\TypedValueDriver;
use Moox\Builder\Support\TypedValueColumns;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\TestCase;

beforeEach(function (): void {
    $this->createItemsTable();
});

it('roundtrips values through typed columns', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);

    $driver = app(TypedValueDriver::class);
    $fields = collect([
        new FieldDefinition('fahrzeugtyp-modell', 'Model', 'text'),
        new FieldDefinition('bruttolistenpreis', 'Price', 'number'),
        new FieldDefinition('erstzulassung', 'Registered', 'date'),
    ]);

    $driver->save('item', $record, [
        'fahrzeugtyp-modell' => 'Golf GTI',
        'bruttolistenpreis' => '32990',
        'erstzulassung' => '2020-03-15',
    ], $fields);

    expect(FieldValue::query()->forRecord('item', $record->getKey())->count())->toBe(3);

    $loaded = $driver->load('item', $record->fresh(), $fields);

    expect($loaded['fahrzeugtyp-modell'])->toBe('Golf GTI')
        ->and($loaded['bruttolistenpreis'])->toEqual(32990)
        ->and($loaded['erstzulassung']->format('Y-m-d'))->toBe('2020-03-15');
});

it('clears stale typed columns when field type changes', function (): void {
    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => 1,
        'field_name' => 'price',
        'value_string' => 'old',
        'value_decimal' => null,
    ]);

    $record = TestItem::query()->create(['title' => 'Demo']);
    $driver = app(TypedValueDriver::class);

    $driver->save('item', $record, ['price' => 19999], collect([
        new FieldDefinition('price', 'Price', 'number'),
    ]));

    $row = FieldValue::query()->forRecord('item', $record->getKey())->first();

    expect($row?->value_string)->toBeNull()
        ->and((float) $row?->value_decimal)->toBe(19999.0);
});

it('maps field types to typed columns', function (): void {
    expect(TypedValueColumns::columnForType('number'))->toBe('value_decimal')
        ->and(TypedValueColumns::columnForType('date'))->toBe('value_date')
        ->and(TypedValueColumns::columnForType('multiselect'))->toBe('value_json')
        ->and(TypedValueColumns::columnForType('text'))->toBe('value_string');
});
