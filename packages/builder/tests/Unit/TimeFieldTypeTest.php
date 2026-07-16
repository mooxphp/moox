<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/TestItemResource.php';

use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Types\TimeFieldType;
use Moox\Builder\Models\Field;
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

    $group = FieldGroup::query()->create([
        'name' => 'Vehicle schedule',
        'slug' => 'vehicle-schedule',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'besichtigungszeit',
        'label' => 'Bevorzugte Besichtigungszeit',
        'type' => 'time',
        'sort' => 0,
        'config' => ['default' => '14:00'],
        'validation' => ['required' => false, 'rules' => []],
    ]);
});

it('applies time defaults on runtime components', function (): void {
    $field = new FieldDefinition(
        name: 'besichtigungszeit',
        label: 'Bevorzugte Besichtigungszeit',
        type: 'time',
        config: ['default' => '14:00'],
    );

    $component = (new TimeFieldType)->formComponent($field);

    expect($component->getDefaultState())->toBeInstanceOf(Carbon::class)
        ->and($component->getDefaultState()->format('H:i'))->toBe('14:00')
        ->and($component->isNative())->toBeTrue();
});

it('casts submitted time values using the configured display format', function (): void {
    $fieldType = new TimeFieldType;

    $field = new FieldDefinition(
        name: 'besichtigungszeit',
        label: 'Bevorzugte Besichtigungszeit',
        type: 'time',
        config: ['displayFormat' => 'H:i:s'],
    );

    expect($fieldType->castValue('14:00', $field))->toBe('14:00:00')
        ->and($fieldType->castValue(now()->setTime(9, 30), $field))->toBe('09:30:00')
        ->and($fieldType->castValue('2026-06-16 14:00:00', $field))->toBe('14:00:00');
});

it('resolves current time when default now is enabled', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'besichtigungszeit',
        label: 'Bevorzugte Besichtigungszeit',
        type: 'time',
        config: ['defaultNow' => true],
    );

    $resolved = $capability->resolveForField($field);

    expect($resolved)->toBeInstanceOf(Carbon::class)
        ->and($resolved->format('H:i'))->toBe(now()->format('H:i'));
});

it('casts submitted time values to H:i strings by default', function (): void {
    $fieldType = new TimeFieldType;

    expect($fieldType->castValue('14:00'))->toBe('14:00')
        ->and($fieldType->castValue(now()->setTime(9, 30)))->toBe('09:30')
        ->and($fieldType->castValue('2026-06-16 14:00:00'))->toBe('14:00');
});

it('persists configured time defaults when the form value is empty on create', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);

    app(CustomFieldsManager::class)->saveFromFormData(
        TestItemResource::class,
        $record,
        ['besichtigungszeit' => null],
    );

    $stored = FieldValue::query()
        ->forRecord('item', $record->getKey())
        ->where('field_name', 'besichtigungszeit')
        ->first();

    expect($stored?->value_string)->toBe('14:00');
});

it('persists current time when default now is enabled on create', function (): void {
    FieldGroup::query()->where('slug', 'vehicle-schedule')->delete();
    Cache::forget(DefinitionRegistry::CACHE_KEY);

    FieldGroup::query()->create([
        'name' => 'Vehicle schedule now',
        'slug' => 'vehicle-schedule-now',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ])->fields()->create([
        'name' => 'besichtigungszeit',
        'label' => 'Bevorzugte Besichtigungszeit',
        'type' => 'time',
        'sort' => 0,
        'config' => ['defaultNow' => true],
        'validation' => ['required' => false, 'rules' => []],
    ]);

    $record = TestItem::query()->create(['title' => 'Demo']);

    app(CustomFieldsManager::class)->saveFromFormData(
        TestItemResource::class,
        $record,
        [],
    );

    $stored = FieldValue::query()
        ->forRecord('item', $record->getKey())
        ->where('field_name', 'besichtigungszeit')
        ->first();

    expect($stored?->value_string)->toBe(now()->format('H:i'));
});

it('syncs time default values through field group persistence', function (): void {
    FieldGroup::query()->where('slug', 'vehicle-schedule')->delete();
    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $group = FieldGroup::query()->create([
        'name' => 'Sync time',
        'slug' => 'sync-time',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Sync time',
        'slug' => 'sync-time',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'besichtigungszeit',
                'label' => 'Bevorzugte Besichtigungszeit',
                'type' => 'time',
                'required' => false,
                'config' => ['default' => '09:30', 'defaultNow' => false],
            ],
        ],
    ]);

    expect($group->fields()->where('name', 'besichtigungszeit')->value('config'))->toMatchArray([
        'default' => '09:30',
        'defaultNow' => false,
        'displayFormat' => 'H:i',
    ]);
});

it('builds temporal default controls for time fields in the admin', function (): void {
    $fields = app(DefaultValue::class)->builderFieldsFor('time');

    expect($fields)->toHaveCount(2)
        ->and($fields[0])->toBeInstanceOf(Toggle::class)
        ->and($fields[1])->toBeInstanceOf(TimePicker::class)
        ->and($fields[1]->isNative())->toBeTrue();
});

it('normalizes stored time strings for form hydration', function (): void {
    $capability = app(DefaultValue::class);

    $normalized = $capability->normalizeTimeValue('14:00');

    expect($normalized)->toBeInstanceOf(Carbon::class)
        ->and($normalized->format('H:i'))->toBe('14:00')
        ->and($capability->normalizeTimeValue('2026-06-16 14:00:00')?->format('H:i'))->toBe('14:00');
});
