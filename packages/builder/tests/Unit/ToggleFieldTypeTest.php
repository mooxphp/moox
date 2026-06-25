<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/TestItemResource.php';

use Illuminate\Support\Facades\Cache;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Types\ToggleFieldType;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\Support\TestItemResource;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->createItemsTable();

    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $group = FieldGroup::query()->create([
        'name' => 'Vehicle options',
        'slug' => 'vehicle-options',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'unfallfrei',
        'label' => 'Unfallfrei',
        'type' => 'toggle',
        'sort' => 0,
        'config' => ['default' => true],
        'validation' => ['required' => false, 'rules' => []],
    ]);
});

it('applies configured toggle defaults at runtime', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'unfallfrei',
        label: 'Unfallfrei',
        type: 'toggle',
        config: ['default' => true],
    );

    $component = (new ToggleFieldType)->formComponent($field);

    expect($component->getDefaultState())->toBeTrue()
        ->and($capability->hasConfiguredDefault($field))->toBeTrue()
        ->and($capability->resolveForField($field))->toBeTrue();
});

it('treats false as a configured toggle default', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'unfallfrei',
        label: 'Unfallfrei',
        type: 'toggle',
        config: ['default' => false],
    );

    expect($capability->hasConfiguredDefault($field))->toBeTrue()
        ->and($capability->resolveForField($field))->toBeFalse()
        ->and((new ToggleFieldType)->formComponent($field)->getDefaultState())->toBeFalse();
});

it('persists configured toggle defaults when the form value is false on create', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);

    app(CustomFieldsManager::class)->saveFromFormData(
        TestItemResource::class,
        $record,
        ['unfallfrei' => false],
    );

    $stored = FieldValue::query()
        ->forRecord('item', $record->getKey())
        ->where('field_name', 'unfallfrei')
        ->first();

    expect($stored?->value_boolean)->toBeTrue();
});

it('does not reapply toggle defaults when a stored value exists on update', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);

    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => $record->getKey(),
        'field_name' => 'unfallfrei',
        'value_boolean' => false,
    ]);

    app(CustomFieldsManager::class)->saveFromFormData(
        TestItemResource::class,
        $record,
        ['unfallfrei' => false],
    );

    $stored = FieldValue::query()
        ->forRecord('item', $record->getKey())
        ->where('field_name', 'unfallfrei')
        ->first();

    expect($stored?->value_boolean)->toBeFalse();
});

it('resolves toggle defaults from cached field group definitions', function (): void {
    $field = app(DefinitionRegistry::class)
        ->fieldGroupsFor(TestItemResource::customFieldsLocationContext())
        ->first()
        ?->fields
        ->firstWhere('name', 'unfallfrei');

    expect($field)->not->toBeNull()
        ->and(app(DefaultValue::class)->resolveForField($field))->toBeTrue();
});
