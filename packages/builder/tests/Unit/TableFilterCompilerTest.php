<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/TestItemResource.php';

use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Support\Facades\Cache;
use Moox\Builder\Compiler\TableFilterCompiler;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Support\CustomFieldTableFilterQuery;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\Support\TestItemResource;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->createItemsTable();

    FieldGroup::query()->delete();

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();
});

it('builds table filters for choice and toggle fields marked show in filter', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'List filters',
        'slug' => 'list-filters',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'fuel',
        'label' => 'Fuel',
        'type' => 'select',
        'settings' => ['show_in_filter' => true],
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ])->options()->createMany([
        ['label' => 'Petrol', 'value' => 'petrol', 'sort' => 0],
        ['label' => 'Diesel', 'value' => 'diesel', 'sort' => 1],
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'accident_free',
        'label' => 'Accident free',
        'type' => 'toggle',
        'settings' => ['show_in_filter' => true],
        'sort' => 1,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'notes',
        'label' => 'Notes',
        'type' => 'text',
        'settings' => ['show_in_filter' => true],
        'sort' => 2,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    $filters = TestItemResource::customFieldFilters();

    expect($filters)->toHaveCount(2)
        ->and(collect($filters)->map(fn ($filter) => $filter->getName())->all())->toBe(['fuel', 'accident_free'])
        ->and($filters[0])->toBeInstanceOf(SelectFilter::class)
        ->and($filters[1])->toBeInstanceOf(TernaryFilter::class);
});

it('filters list queries by custom field values', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Fuel filter',
        'slug' => 'fuel-filter',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    $field = Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'fuel',
        'label' => 'Fuel',
        'type' => 'select',
        'settings' => ['show_in_filter' => true],
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    $field->options()->createMany([
        ['label' => 'Petrol', 'value' => 'petrol', 'sort' => 0],
        ['label' => 'Diesel', 'value' => 'diesel', 'sort' => 1],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    $petrolItem = TestItem::query()->create(['title' => 'Petrol car']);
    $dieselItem = TestItem::query()->create(['title' => 'Diesel car']);

    app(CustomFieldsManager::class)->saveFromFormData(TestItemResource::class, $petrolItem, [
        'fuel' => 'petrol',
    ]);
    app(CustomFieldsManager::class)->saveFromFormData(TestItemResource::class, $dieselItem, [
        'fuel' => 'diesel',
    ]);

    $definition = app(DefinitionRegistry::class)
        ->fieldGroupsFor(TestItemResource::customFieldsLocationContext())
        ->flatMap(fn ($group) => $group->fields)
        ->firstWhere('name', 'fuel');

    expect($definition)->not->toBeNull();

    $filtered = app(CustomFieldTableFilterQuery::class)->applyEquals(
        TestItem::query(),
        $definition,
        'item',
        TestItem::class,
        'petrol',
    )->pluck('id')->all();

    expect($filtered)->toBe([$petrolItem->getKey()]);
});

it('filters list queries by single relation custom fields', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Relation filter',
        'slug' => 'relation-filter',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'linked_item',
        'label' => 'Linked item',
        'type' => 'relation',
        'config' => ['related_entity' => 'item', 'multiple' => false],
        'settings' => ['show_in_filter' => true],
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    $filters = TestItemResource::customFieldFilters();

    expect($filters)->toHaveCount(1)
        ->and($filters[0])->toBeInstanceOf(SelectFilter::class)
        ->and($filters[0]->getName())->toBe('linked_item');

    $target = TestItem::query()->create(['title' => 'Target']);
    $linked = TestItem::query()->create(['title' => 'Linked']);
    $other = TestItem::query()->create(['title' => 'Other']);

    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => $linked->getKey(),
        'field_name' => 'linked_item',
        'locale' => 'en_US',
        'value_json' => $target->getKey(),
    ]);

    $definition = app(DefinitionRegistry::class)
        ->fieldGroupsFor(TestItemResource::customFieldsLocationContext())
        ->flatMap(fn ($group) => $group->fields)
        ->firstWhere('name', 'linked_item');

    expect($definition)->not->toBeNull();

    $filtered = app(CustomFieldTableFilterQuery::class)->applyEquals(
        TestItem::query(),
        $definition,
        'item',
        TestItem::class,
        $target->getKey(),
    )->pluck('id')->all();

    expect($filtered)->toBe([$linked->getKey()])
        ->and($filtered)->not->toContain($other->getKey());
});

it('does not build filters when show in filter is disabled', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Hidden filter',
        'slug' => 'hidden-filter',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'fuel',
        'label' => 'Fuel',
        'type' => 'select',
        'settings' => ['show_in_filter' => false],
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    expect(TestItemResource::customFieldFilters())->toBe([]);
});

it('does not build filters for select fields without options', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Empty options filter',
        'slug' => 'empty-options-filter',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'fuel',
        'label' => 'Fuel',
        'type' => 'select',
        'settings' => ['show_in_filter' => true],
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    expect(TestItemResource::customFieldFilters())->toBe([]);
});

it('does not build filters for multiple relation fields', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Multiple relation filter',
        'slug' => 'multiple-relation-filter',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'linked_items',
        'label' => 'Linked items',
        'type' => 'relation',
        'config' => ['related_entity' => 'item', 'multiple' => true],
        'settings' => ['show_in_filter' => true],
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    expect(TestItemResource::customFieldFilters())->toBe([]);
});

it('filters list queries by toggle custom fields', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Toggle filter',
        'slug' => 'toggle-filter',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'accident_free',
        'label' => 'Accident free',
        'type' => 'toggle',
        'settings' => ['show_in_filter' => true],
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    $safeItem = TestItem::query()->create(['title' => 'Safe car']);
    $damagedItem = TestItem::query()->create(['title' => 'Damaged car']);

    app(CustomFieldsManager::class)->saveFromFormData(TestItemResource::class, $safeItem, [
        'accident_free' => true,
    ]);
    app(CustomFieldsManager::class)->saveFromFormData(TestItemResource::class, $damagedItem, [
        'accident_free' => false,
    ]);

    $definition = app(DefinitionRegistry::class)
        ->fieldGroupsFor(TestItemResource::customFieldsLocationContext())
        ->flatMap(fn ($group) => $group->fields)
        ->firstWhere('name', 'accident_free');

    expect($definition)->not->toBeNull();

    $filtered = app(CustomFieldTableFilterQuery::class)->applyEquals(
        TestItem::query(),
        $definition,
        'item',
        TestItem::class,
        true,
    )->pluck('id')->all();

    expect($filtered)->toBe([$safeItem->getKey()])
        ->and($filtered)->not->toContain($damagedItem->getKey());
});

it('syncs show in filter through field group persistence', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Persist filter',
        'slug' => 'persist-filter',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(\Moox\Builder\Services\FieldGroupPersistence::class)->sync($group, [
        'name' => 'Persist filter',
        'slug' => 'persist-filter',
        'target_entities' => ['item'],
        'fields' => [[
            'name' => 'fuel',
            'label' => 'Fuel',
            'type' => 'select',
            'settings' => ['show_in_filter' => true],
            'options' => [
                ['label' => 'Petrol', 'value' => 'petrol'],
            ],
        ]],
    ]);

    $field = Field::query()->where('name', 'fuel')->first();

    expect($field)->not->toBeNull()
        ->and($field->settings['show_in_filter'] ?? false)->toBeTrue();
});
