<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/TestItemResource.php';

use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Capabilities\MaxValue;
use Moox\Builder\FieldTypes\Capabilities\MinValue;
use Moox\Builder\FieldTypes\Types\RangeFieldType;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Services\FieldGroupValidator;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\Support\TestItemResource;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->createItemsTable();
});

it('applies min and max bounds on range sliders', function (): void {
    $field = new FieldDefinition(
        name: 'power',
        label: 'Power',
        type: 'range',
        config: ['min' => 50, 'max' => 500],
    );

    $component = (new RangeFieldType)->formComponent($field);

    expect($component->getMinValue())->toBe(50)
        ->and($component->getMaxValue())->toBe(500);
});

it('applies aligned range defaults on runtime components', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'motorleistung',
        label: 'Motorleistung',
        type: 'range',
        config: ['min' => 50, 'max' => 500, 'step' => 5, 'default' => 245],
    );

    $component = (new RangeFieldType)->formComponent($field);

    expect($component->getDefaultState())->toBe(245)
        ->and($capability->resolveForField($field))->toBe(245);
});

it('falls back to the configured min when no range default is set', function (): void {
    $field = new FieldDefinition(
        name: 'motorleistung',
        label: 'Motorleistung',
        type: 'range',
        config: ['min' => 50, 'max' => 500, 'step' => 5],
    );

    $component = (new RangeFieldType)->formComponent($field);

    expect($component->getDefaultState())->toBe(50);
});

it('compiles range fields with configured defaults from field groups', function (): void {
    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $group = FieldGroup::query()->create([
        'name' => 'Vehicle options',
        'slug' => 'vehicle-range-runtime-default',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'motorleistung',
        'label' => 'Motorleistung',
        'type' => 'range',
        'sort' => 0,
        'config' => ['min' => 50, 'max' => 500, 'step' => 5, 'default' => 245],
        'validation' => ['required' => false, 'rules' => []],
    ]);

    $field = app(DefinitionRegistry::class)
        ->fieldGroupsFor(TestItemResource::customFieldsLocationContext())
        ->first()
        ?->fields
        ->firstWhere('name', 'motorleistung');

    expect($field)->not->toBeNull();

    $component = (new RangeFieldType)->formComponent($field);

    expect($component->getDefaultState())->toBe(245);
});

it('ignores range defaults that do not align with the step', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'motorleistung',
        label: 'Motorleistung',
        type: 'range',
        config: ['min' => 10, 'max' => 100, 'step' => 10, 'default' => 12],
    );

    expect($capability->resolveForField($field))->toBeNull()
        ->and($capability->rangeDefaultIsValid(20, $field->config))->toBeTrue()
        ->and($capability->rangeDefaultIsValid(12, $field->config))->toBeFalse();
});

it('detects when a slider still shows the min fallback instead of the configured default', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'motorleistung',
        label: 'Motorleistung',
        type: 'range',
        config: ['min' => 50, 'max' => 500, 'step' => 5, 'default' => 245],
    );

    expect($capability->shouldReplaceSliderFallbackState($field, 50))->toBeTrue()
        ->and($capability->shouldReplaceSliderFallbackState($field, 245))->toBeFalse()
        ->and($capability->shouldReplaceSliderFallbackState($field, 255))->toBeFalse();
});

it('persists configured range defaults when the form value is empty on create', function (): void {
    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $group = FieldGroup::query()->create([
        'name' => 'Vehicle options',
        'slug' => 'vehicle-range-default',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'motorleistung',
        'label' => 'Motorleistung',
        'type' => 'range',
        'sort' => 0,
        'config' => ['min' => 50, 'max' => 500, 'step' => 5, 'default' => 245],
        'validation' => ['required' => false, 'rules' => []],
    ]);

    $record = TestItem::query()->create(['title' => 'Demo']);

    app(CustomFieldsManager::class)->saveFromFormData(
        TestItemResource::class,
        $record,
        ['motorleistung' => null],
    );

    $stored = FieldValue::query()
        ->forRecord('item', $record->getKey())
        ->where('field_name', 'motorleistung')
        ->first();

    expect((float) $stored?->value_decimal)->toBe(245.0);
});

it('requires range max to be greater than min when saving field groups', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Vehicle',
        'slug' => 'vehicle-range',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    expect(fn () => app(FieldGroupValidator::class)->validate($group, [
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'motorleistung',
                'label' => 'Motorleistung',
                'type' => 'range',
                'config' => ['min' => 500, 'max' => 50],
            ],
        ],
    ]))->toThrow(ValidationException::class);
});

it('rejects equal min and max values for range fields', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Vehicle',
        'slug' => 'vehicle-range-equal',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    expect(fn () => app(FieldGroupValidator::class)->validate($group, [
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'motorleistung',
                'label' => 'Motorleistung',
                'type' => 'range',
                'config' => ['min' => 100, 'max' => 100],
            ],
        ],
    ]))->toThrow(ValidationException::class);
});

it('rejects misaligned range defaults when saving field groups', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Vehicle',
        'slug' => 'vehicle-range-default-invalid',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    expect(fn () => app(FieldGroupValidator::class)->validate($group, [
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'motorleistung',
                'label' => 'Motorleistung',
                'type' => 'range',
                'config' => ['min' => 10, 'max' => 100, 'step' => 10, 'default' => 12],
            ],
        ],
    ]))->toThrow(ValidationException::class);
});

it('builds cross validated min and max inputs for range fields in the admin', function (): void {
    $minField = app(MinValue::class)->builderFieldsFor('range')[0];
    $maxField = app(MaxValue::class)->builderFieldsFor('range')[0];

    expect($minField)->toBeInstanceOf(TextInput::class)
        ->and($maxField)->toBeInstanceOf(TextInput::class)
        ->and($minField->isLive())->toBeTrue()
        ->and($maxField->isLive())->toBeTrue();
});

it('builds a step aware default input for range fields in the admin', function (): void {
    $fields = app(DefaultValue::class)->builderFieldsFor('range');

    expect($fields)->toHaveCount(1)
        ->and($fields[0])->toBeInstanceOf(TextInput::class)
        ->and($fields[0]->isLive())->toBeTrue();
});
