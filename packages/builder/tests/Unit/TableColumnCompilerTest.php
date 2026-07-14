<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/TestItemResource.php';
require_once __DIR__.'/../Support/TestCategoryLike.php';
require_once __DIR__.'/../Support/TestCategoryLikeTranslation.php';
require_once __DIR__.'/../Support/TestCategoryLikeResource.php';

use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Moox\Builder\Compiler\TableColumnCompiler;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Data\LocationContext;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Registry\EntityRegistry;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Support\BuilderLocaleResolver;
use Moox\Builder\Support\RelationTableColumnQuery;
use Moox\Builder\Tests\Support\TestCategoryLike;
use Moox\Builder\Tests\Support\TestCategoryLikeResource;
use Moox\Builder\Tests\Support\TestCategoryLikeTranslation;
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

function seedTableColumnFieldGroup(): FieldGroup
{
    $group = FieldGroup::query()->create([
        'name' => 'List columns',
        'slug' => 'list-columns',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    foreach ([
        ['name' => 'color', 'label' => 'Color', 'type' => 'text', 'settings' => ['show_in_table' => true]],
        ['name' => 'active', 'label' => 'Active', 'type' => 'toggle', 'settings' => ['show_in_table' => true]],
        ['name' => 'gallery', 'label' => 'Gallery', 'type' => 'gallery', 'settings' => ['show_in_table' => true]],
        ['name' => 'secret', 'label' => 'Secret', 'type' => 'password', 'settings' => ['show_in_table' => true]],
        ['name' => 'hidden', 'label' => 'Hidden', 'type' => 'text', 'settings' => ['show_in_table' => false]],
    ] as $index => $field) {
        Field::query()->create([
            'field_group_id' => $group->getKey(),
            'name' => $field['name'],
            'label' => $field['label'],
            'type' => $field['type'],
            'settings' => $field['settings'],
            'sort' => $index,
            'validation' => ['required' => false, 'rules' => []],
        ]);
    }

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    return $group;
}

it('builds table columns for scalar and image fields marked show in table', function (): void {
    seedTableColumnFieldGroup();

    $columns = TestItemResource::customFieldColumns();

    expect($columns)->toHaveCount(3)
        ->and(collect($columns)->map(fn ($column) => $column->getName())->all())->toBe(['color', 'active', 'gallery'])
        ->and($columns[0])->toBeInstanceOf(TextColumn::class)
        ->and($columns[1])->toBeInstanceOf(IconColumn::class)
        ->and($columns[2])->toBeInstanceOf(ImageColumn::class);
});

it('builds color fields as dedicated color columns', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Color columns',
        'slug' => 'color-columns',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'brand_color',
        'label' => 'Brand color',
        'type' => 'color',
        'settings' => [
            'show_in_table' => true,
            'badge' => true,
            'color' => 'success',
            'icon' => 'heroicon-o-star',
        ],
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    $columns = TestItemResource::customFieldColumns();

    expect($columns)->toHaveCount(1)
        ->and($columns[0])->toBeInstanceOf(ColorColumn::class)
        ->and($columns[0]->isSortable())->toBeTrue()
        ->and($columns[0]->isSearchable())->toBeTrue();
});

it('builds image fields as non sortable, non searchable image columns', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Media columns',
        'slug' => 'media-columns',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'hero',
        'label' => 'Hero',
        'type' => 'image',
        'settings' => ['show_in_table' => true, 'hidden_by_default' => false],
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    $columns = TestItemResource::customFieldColumns();

    expect($columns)->toHaveCount(1)
        ->and($columns[0])->toBeInstanceOf(ImageColumn::class)
        ->and($columns[0]->isSortable())->toBeFalse()
        ->and($columns[0]->isSearchable())->toBeFalse()
        ->and($columns[0]->isToggledHiddenByDefault())->toBeFalse();
});

it('applies display format and placeholder to date and number columns', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Formatted columns',
        'slug' => 'formatted-columns',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'starts_on',
        'label' => 'Starts on',
        'type' => 'date',
        'config' => ['displayFormat' => 'd.m.Y'],
        'settings' => ['show_in_table' => true],
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'price',
        'label' => 'Price',
        'type' => 'number',
        'config' => ['step' => 0.01],
        'settings' => ['show_in_table' => true],
        'sort' => 1,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'title',
        'label' => 'Title',
        'type' => 'text',
        'settings' => ['show_in_table' => true],
        'sort' => 2,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    $columns = collect(TestItemResource::customFieldColumns())->keyBy(fn ($column) => $column->getName());

    expect($columns['starts_on']->isDate())->toBeTrue()
        ->and($columns['starts_on']->formatState('2026-06-16'))->toBe('16.06.2026')
        ->and($columns['starts_on']->getPlaceholder())->toBe('—')
        ->and($columns['price']->isNumeric())->toBeTrue()
        ->and($columns['title']->getPlaceholder())->toBe('—');
});

it('applies datetime display format from field config', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Datetime column',
        'slug' => 'datetime-column',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'starts_at',
        'label' => 'Starts at',
        'type' => 'datetime',
        'config' => ['displayFormat' => 'd.m.Y H:i'],
        'settings' => ['show_in_table' => true],
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    $column = TestItemResource::customFieldColumns()[0];

    expect($column->isDateTime())->toBeTrue()
        ->and($column->formatState('2026-06-16T14:30:00+00:00'))->toBe('16.06.2026 14:30');
});

it('renders boolean toggle columns with check and cross icons', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Flags',
        'slug' => 'flags',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'featured',
        'label' => 'Featured',
        'type' => 'toggle',
        'settings' => ['show_in_table' => true],
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    $columns = TestItemResource::customFieldColumns();

    expect($columns)->toHaveCount(1)
        ->and($columns[0])->toBeInstanceOf(IconColumn::class)
        ->and($columns[0]->isBoolean())->toBeTrue()
        ->and($columns[0]->isSortable())->toBeTrue()
        ->and($columns[0]->isSearchable())->toBeFalse()
        ->and($columns[0]->getTrueIcon())->toBe(Heroicon::OutlinedCheckCircle)
        ->and($columns[0]->getFalseIcon())->toBe(Heroicon::OutlinedXCircle)
        ->and($columns[0]->getTrueColor())->toBe('success')
        ->and($columns[0]->getFalseColor())->toBe('danger');
});

it('defaults image column shape and size', function (): void {
    $field = FieldDefinition::fromArray([
        'name' => 'hero',
        'label' => 'Hero',
        'type' => 'image',
        'settings' => ['show_in_table' => true],
    ]);

    expect($field->columnImageShape())->toBeNull()
        ->and($field->columnImageSize())->toBe('md');
});

it('reads image column shape and size from field definition', function (): void {
    $field = FieldDefinition::fromArray([
        'name' => 'hero',
        'label' => 'Hero',
        'type' => 'image',
        'settings' => [
            'show_in_table' => true,
            'image_shape' => 'circular',
            'image_size' => 'lg',
        ],
    ]);

    expect($field->columnImageShape())->toBe('circular')
        ->and($field->columnImageSize())->toBe('lg');
});

it('applies circular shape and size to compiled image columns', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Media shape',
        'slug' => 'media-shape',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'avatar',
        'label' => 'Avatar',
        'type' => 'image',
        'settings' => [
            'show_in_table' => true,
            'image_shape' => 'circular',
            'image_size' => 'lg',
        ],
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    $columns = TestItemResource::customFieldColumns();

    expect($columns)->toHaveCount(1)
        ->and($columns[0])->toBeInstanceOf(ImageColumn::class)
        ->and($columns[0]->isCircular())->toBeTrue()
        ->and($columns[0]->getImageHeight())->toBe('56px');
});

it('round trips image shape and size through field group persistence', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Sync',
        'slug' => 'sync-image-settings',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Sync',
        'slug' => 'sync-image-settings',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'hero',
                'label' => 'Hero',
                'type' => 'image',
                'required' => false,
                'settings' => [
                    'show_in_table' => true,
                    'image_shape' => 'square',
                    'image_size' => 'sm',
                ],
            ],
        ],
    ]);

    $rows = app(FieldGroupPersistence::class)->fieldRowsForForm($group->fresh());

    expect($rows[0]['settings'])->toMatchArray([
        'image_shape' => 'square',
        'image_size' => 'sm',
    ]);
});

it('exposes field definition show in table helper', function (): void {
    $field = FieldDefinition::fromArray([
        'name' => 'color',
        'label' => 'Color',
        'type' => 'text',
        'settings' => ['show_in_table' => true],
    ]);

    expect($field->showInTable())->toBeTrue();
});

it('round trips show in table through field group persistence', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Sync',
        'slug' => 'sync-table-setting',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Sync',
        'slug' => 'sync-table-setting',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'color',
                'label' => 'Color',
                'type' => 'text',
                'required' => false,
                'settings' => ['show_in_table' => true],
            ],
        ],
    ]);

    $field = $group->fields()->where('name', 'color')->first();

    expect($field)->not->toBeNull()
        ->and($field->settings['show_in_table'] ?? false)->toBeTrue();

    $rows = app(FieldGroupPersistence::class)->fieldRowsForForm($group->fresh());

    expect($rows[0]['settings']['show_in_table'] ?? false)->toBeTrue();
});

it('sorts records by custom field column subquery', function (): void {
    seedTableColumnFieldGroup();

    $alpha = TestItem::query()->create(['title' => 'Alpha']);
    $beta = TestItem::query()->create(['title' => 'Beta']);

    $locale = app(BuilderLocaleResolver::class)->defaultLocale();
    $fields = app(CustomFieldsManager::class)->fieldsForEntity('item');

    app(CustomFieldsManager::class)->saveValues('item', $alpha, ['color' => 'Apple'], $fields, $locale);
    app(CustomFieldsManager::class)->saveValues('item', $beta, ['color' => 'Banana'], $fields, $locale);

    $valuesTable = (new FieldValue)->getTable();
    $recordKey = (new TestItem)->getQualifiedKeyName();

    $orderedIds = TestItem::query()
        ->orderBy(
            FieldValue::query()
                ->select('value_string')
                ->from($valuesTable)
                ->whereColumn("{$valuesTable}.record_id", $recordKey)
                ->where("{$valuesTable}.entity", 'item')
                ->where("{$valuesTable}.field_name", 'color')
                ->where("{$valuesTable}.locale", $locale)
                ->limit(1),
            'asc',
        )
        ->pluck('id')
        ->all();

    expect($orderedIds)->toBe([$alpha->getKey(), $beta->getKey()]);
});

it('searches records by custom field column through the eloquent builder', function (): void {
    seedTableColumnFieldGroup();

    $match = TestItem::query()->create(['title' => 'Match']);
    $other = TestItem::query()->create(['title' => 'Other']);

    $locale = app(BuilderLocaleResolver::class)->defaultLocale();
    $fields = app(CustomFieldsManager::class)->fieldsForEntity('item');

    app(CustomFieldsManager::class)->saveValues('item', $match, ['color' => 'Golf GTI'], $fields, $locale);
    app(CustomFieldsManager::class)->saveValues('item', $other, ['color' => 'Polo'], $fields, $locale);

    $ids = TestItem::query()
        ->where('color', 'like', '%Golf%')
        ->pluck('id')
        ->all();

    expect($ids)->toBe([$match->getKey()]);
});

it('returns no columns when no field groups match the entity', function (): void {
    FieldGroup::query()->delete();
    Cache::forget(DefinitionRegistry::CACHE_KEY);

    expect(app(TableColumnCompiler::class)->compile(
        app(DefinitionRegistry::class)->fieldGroupsFor(new LocationContext('item')),
        TestItemResource::class,
    ))->toBe([]);
});

it('defaults column settings to sortable, searchable and hidden', function (): void {
    $field = FieldDefinition::fromArray([
        'name' => 'color',
        'label' => 'Color',
        'type' => 'text',
        'settings' => ['show_in_table' => true],
    ]);

    expect($field->isColumnSortable())->toBeTrue()
        ->and($field->isColumnSearchable())->toBeTrue()
        ->and($field->isColumnHiddenByDefault())->toBeTrue();
});

it('honours disabled column settings from field definition', function (): void {
    $field = FieldDefinition::fromArray([
        'name' => 'color',
        'label' => 'Color',
        'type' => 'text',
        'settings' => [
            'show_in_table' => true,
            'sortable' => false,
            'searchable' => false,
            'hidden_by_default' => false,
        ],
    ]);

    expect($field->isColumnSortable())->toBeFalse()
        ->and($field->isColumnSearchable())->toBeFalse()
        ->and($field->isColumnHiddenByDefault())->toBeFalse();
});

it('applies column settings to compiled columns', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Settings columns',
        'slug' => 'settings-columns',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'plain',
        'label' => 'Plain',
        'type' => 'text',
        'settings' => [
            'show_in_table' => true,
            'sortable' => false,
            'searchable' => false,
            'hidden_by_default' => false,
        ],
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    $columns = TestItemResource::customFieldColumns();

    expect($columns)->toHaveCount(1)
        ->and($columns[0]->isSortable())->toBeFalse()
        ->and($columns[0]->isSearchable())->toBeFalse()
        ->and($columns[0]->isToggledHiddenByDefault())->toBeFalse();
});

it('defaults badge, color and icon column settings to disabled', function (): void {
    $field = FieldDefinition::fromArray([
        'name' => 'color',
        'label' => 'Color',
        'type' => 'text',
        'settings' => ['show_in_table' => true],
    ]);

    expect($field->columnBadge())->toBeFalse()
        ->and($field->columnColor())->toBeNull()
        ->and($field->columnIcon())->toBeNull();
});

it('reads badge, color and icon column settings from field definition', function (): void {
    $field = FieldDefinition::fromArray([
        'name' => 'status',
        'label' => 'Status',
        'type' => 'text',
        'settings' => [
            'show_in_table' => true,
            'badge' => true,
            'color' => 'success',
            'icon' => 'heroicon-o-star',
        ],
    ]);

    expect($field->columnBadge())->toBeTrue()
        ->and($field->columnColor())->toBe('success')
        ->and($field->columnIcon())->toBe('heroicon-o-star');
});

it('applies badge, color and icon to compiled text columns', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Presentation columns',
        'slug' => 'presentation-columns',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'status',
        'label' => 'Status',
        'type' => 'text',
        'settings' => [
            'show_in_table' => true,
            'badge' => true,
            'color' => 'success',
            'icon' => 'heroicon-o-star',
        ],
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    $columns = TestItemResource::customFieldColumns();

    expect($columns)->toHaveCount(1)
        ->and($columns[0])->toBeInstanceOf(TextColumn::class)
        ->and($columns[0]->isBadge())->toBeTrue()
        ->and($columns[0]->getColor(null))->toBe('success')
        ->and($columns[0]->getIcon(null))->toBe('heroicon-o-star');
});

it('applies badge, color and icon settings on numeric columns', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Numeric presentation columns',
        'slug' => 'numeric-presentation-columns',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'price',
        'label' => 'Price',
        'type' => 'number',
        'settings' => [
            'show_in_table' => true,
            'badge' => true,
            'color' => 'success',
            'icon' => 'heroicon-o-star',
        ],
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    $columns = TestItemResource::customFieldColumns();

    expect($columns)->toHaveCount(1)
        ->and($columns[0])->toBeInstanceOf(TextColumn::class)
        ->and($columns[0]->isBadge())->toBeTrue()
        ->and($columns[0]->getColor(null))->toBe('success')
        ->and($columns[0]->getIcon(null))->toBe('heroicon-o-star');
});

it('does not build rich text fields as table columns', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Rich text columns',
        'slug' => 'rich-text-columns',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'body',
        'label' => 'Body',
        'type' => 'rich_text',
        'settings' => ['show_in_table' => true],
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    expect(TestItemResource::customFieldColumns())->toBe([]);
});

it('round trips badge, color and icon through field group persistence', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Sync',
        'slug' => 'sync-presentation-settings',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Sync',
        'slug' => 'sync-presentation-settings',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'status',
                'label' => 'Status',
                'type' => 'text',
                'required' => false,
                'settings' => [
                    'show_in_table' => true,
                    'badge' => true,
                    'color' => 'success',
                    'icon' => 'heroicon-o-star',
                ],
            ],
        ],
    ]);

    $rows = app(FieldGroupPersistence::class)->fieldRowsForForm($group->fresh());

    expect($rows[0]['settings'])->toMatchArray([
        'badge' => true,
        'color' => 'success',
        'icon' => 'heroicon-o-star',
    ]);
});

it('round trips column settings through field group persistence', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Sync',
        'slug' => 'sync-column-settings',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Sync',
        'slug' => 'sync-column-settings',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'color',
                'label' => 'Color',
                'type' => 'text',
                'required' => false,
                'settings' => [
                    'show_in_table' => true,
                    'sortable' => false,
                    'searchable' => false,
                    'hidden_by_default' => false,
                ],
            ],
        ],
    ]);

    $rows = app(FieldGroupPersistence::class)->fieldRowsForForm($group->fresh());

    expect($rows[0]['settings'])->toMatchArray([
        'show_in_table' => true,
        'sortable' => false,
        'searchable' => false,
        'hidden_by_default' => false,
    ]);
});

function bindRelationColumnTestEntityRegistry(): void
{
    $registry = new class extends EntityRegistry
    {
        protected function panelResources(): array
        {
            return [TestItemResource::class];
        }
    };

    app()->instance(EntityRegistry::class, $registry);
}

it('builds relation fields as sortable and searchable text columns by default', function (): void {
    bindRelationColumnTestEntityRegistry();

    $group = FieldGroup::query()->create([
        'name' => 'Relation columns',
        'slug' => 'relation-columns',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'linked-item',
        'label' => 'Linked item',
        'type' => 'relation',
        'config' => ['related_entity' => 'item', 'multiple' => false],
        'settings' => ['show_in_table' => true, 'hidden_by_default' => false],
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    $columns = TestItemResource::customFieldColumns();

    expect($columns)->toHaveCount(1)
        ->and($columns[0])->toBeInstanceOf(TextColumn::class)
        ->and($columns[0]->isSortable())->toBeTrue()
        ->and($columns[0]->isSearchable())->toBeTrue()
        ->and($columns[0]->getPlaceholder())->toBe('—');
});

it('sorts records by relation column labels', function (): void {
    bindRelationColumnTestEntityRegistry();

    $group = FieldGroup::query()->create([
        'name' => 'Relation sort',
        'slug' => 'relation-sort',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'linked-item',
        'label' => 'Linked item',
        'type' => 'relation',
        'config' => ['related_entity' => 'item', 'multiple' => false],
        'settings' => ['show_in_table' => true],
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    $alpha = TestItem::query()->create(['title' => 'Alpha target']);
    $beta = TestItem::query()->create(['title' => 'Beta target']);
    $ownerAlpha = TestItem::query()->create(['title' => 'Owner A']);
    $ownerBeta = TestItem::query()->create(['title' => 'Owner B']);

    $locale = app(BuilderLocaleResolver::class)->defaultLocale();
    $fields = app(CustomFieldsManager::class)->fieldsForEntity('item');
    $manager = app(CustomFieldsManager::class);

    $manager->saveValues('item', $ownerBeta, ['linked-item' => $beta->getKey()], $fields, $locale);
    $manager->saveValues('item', $ownerAlpha, ['linked-item' => $alpha->getKey()], $fields, $locale);

    $orderedIds = TestItem::query()
        ->whereKey([$ownerAlpha->getKey(), $ownerBeta->getKey()])
        ->tap(fn ($query) => app(RelationTableColumnQuery::class)->applySort(
            $query,
            $fields->firstWhere('name', 'linked-item'),
            'item',
            $locale,
            (new FieldValue)->getTable(),
            'asc',
        ))
        ->pluck('id')
        ->all();

    expect($orderedIds)->toBe([$ownerAlpha->getKey(), $ownerBeta->getKey()]);
});

it('searches records by relation column labels', function (): void {
    bindRelationColumnTestEntityRegistry();

    $group = FieldGroup::query()->create([
        'name' => 'Relation search',
        'slug' => 'relation-search',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'linked-item',
        'label' => 'Linked item',
        'type' => 'relation',
        'config' => ['related_entity' => 'item', 'multiple' => false],
        'settings' => ['show_in_table' => true],
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    $matchTarget = TestItem::query()->create(['title' => 'Golf target']);
    $otherTarget = TestItem::query()->create(['title' => 'Polo target']);
    $matchOwner = TestItem::query()->create(['title' => 'Match owner']);
    $otherOwner = TestItem::query()->create(['title' => 'Other owner']);

    $locale = app(BuilderLocaleResolver::class)->defaultLocale();
    $fields = app(CustomFieldsManager::class)->fieldsForEntity('item');
    $manager = app(CustomFieldsManager::class);

    $manager->saveValues('item', $matchOwner, ['linked-item' => $matchTarget->getKey()], $fields, $locale);
    $manager->saveValues('item', $otherOwner, ['linked-item' => $otherTarget->getKey()], $fields, $locale);

    $field = $fields->firstWhere('name', 'linked-item');

    $ids = TestItem::query()
        ->whereKey([$matchOwner->getKey(), $otherOwner->getKey()])
        ->tap(fn ($query) => app(RelationTableColumnQuery::class)->applySearch(
            $query,
            $field,
            'item',
            $locale,
            (new FieldValue)->getTable(),
            'Golf',
        ))
        ->pluck('id')
        ->all();

    expect($ids)->toBe([$matchOwner->getKey()]);
});

function bindTranslationBackedRelationColumnTestEntityRegistry(): void
{
    $registry = new class extends EntityRegistry
    {
        protected function panelResources(): array
        {
            return [TestItemResource::class, TestCategoryLikeResource::class];
        }
    };

    app()->instance(EntityRegistry::class, $registry);
}

it('searches records by translation-backed relation column labels', function (): void {
    Schema::dropIfExists('category_translations');
    Schema::dropIfExists('categories');

    Schema::create('categories', function (Blueprint $table): void {
        $table->id();
    });

    Schema::create('category_translations', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('category_id');
        $table->string('locale');
        $table->string('title')->nullable();
    });

    bindTranslationBackedRelationColumnTestEntityRegistry();

    $group = FieldGroup::query()->create([
        'name' => 'Translation relation search',
        'slug' => 'translation-relation-search',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'linked-category',
        'label' => 'Linked category',
        'type' => 'relation',
        'config' => ['related_entity' => 'test_category_like', 'multiple' => false],
        'settings' => ['show_in_table' => true],
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    $matchTarget = TestCategoryLike::query()->create();
    TestCategoryLikeTranslation::query()->create([
        'category_id' => $matchTarget->getKey(),
        'locale' => 'en_US',
        'title' => 'Golf category',
    ]);
    $otherTarget = TestCategoryLike::query()->create();
    TestCategoryLikeTranslation::query()->create([
        'category_id' => $otherTarget->getKey(),
        'locale' => 'en_US',
        'title' => 'Polo category',
    ]);
    $matchOwner = TestItem::query()->create(['title' => 'Match owner']);
    $otherOwner = TestItem::query()->create(['title' => 'Other owner']);

    $locale = app(BuilderLocaleResolver::class)->defaultLocale();
    $fields = app(CustomFieldsManager::class)->fieldsForEntity('item');
    $manager = app(CustomFieldsManager::class);

    $manager->saveValues('item', $matchOwner, ['linked-category' => $matchTarget->getKey()], $fields, $locale);
    $manager->saveValues('item', $otherOwner, ['linked-category' => $otherTarget->getKey()], $fields, $locale);

    $field = $fields->firstWhere('name', 'linked-category');

    $ids = TestItem::query()
        ->whereKey([$matchOwner->getKey(), $otherOwner->getKey()])
        ->tap(fn ($query) => app(RelationTableColumnQuery::class)->applySearch(
            $query,
            $field,
            'item',
            $locale,
            (new FieldValue)->getTable(),
            'Golf',
        ))
        ->pluck('id')
        ->all();

    expect($ids)->toBe([$matchOwner->getKey()]);
});

it('applies badge presentation to single relation columns when enabled', function (): void {
    bindRelationColumnTestEntityRegistry();

    $group = FieldGroup::query()->create([
        'name' => 'Relation badge',
        'slug' => 'relation-badge',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'linked-item',
        'label' => 'Linked item',
        'type' => 'relation',
        'config' => ['related_entity' => 'item', 'multiple' => false],
        'settings' => ['show_in_table' => true, 'badge' => true],
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    $columns = TestItemResource::customFieldColumns();

    expect($columns)->toHaveCount(1)
        ->and($columns[0])->toBeInstanceOf(TextColumn::class)
        ->and($columns[0]->isBadge())->toBeTrue();
});

it('formats relation column state as resolved labels', function (): void {
    $compiler = app(TableColumnCompiler::class);
    $reflection = new ReflectionClass($compiler);
    $format = $reflection->getMethod('formatRelationColumnState');
    $format->setAccessible(true);

    expect($format->invoke($compiler, ['id' => 1, 'label' => 'Alpha']))->toBe('Alpha')
        ->and($format->invoke($compiler, [
            ['id' => 1, 'label' => 'Alpha'],
            ['id' => 2, 'label' => 'Beta'],
        ]))->toBe('Alpha, Beta')
        ->and($format->invoke($compiler, null))->toBeNull();
});

it('resolves relation column labels from saved field values', function (): void {
    bindRelationColumnTestEntityRegistry();

    $group = FieldGroup::query()->create([
        'name' => 'Relation values',
        'slug' => 'relation-values',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'linked-item',
        'label' => 'Linked item',
        'type' => 'relation',
        'config' => ['related_entity' => 'item', 'multiple' => false],
        'settings' => ['show_in_table' => true],
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    $owner = TestItem::query()->create(['title' => 'Owner']);
    $target = TestItem::query()->create(['title' => 'Linked Title']);

    $locale = app(BuilderLocaleResolver::class)->defaultLocale();
    $fields = app(CustomFieldsManager::class)->fieldsForEntity('item');

    app(CustomFieldsManager::class)->saveValues('item', $owner, ['linked-item' => $target->getKey()], $fields, $locale);

    $field = $fields->firstWhere('name', 'linked-item');
    $compiler = app(TableColumnCompiler::class);
    $reflection = new ReflectionClass($compiler);
    $resolvePresented = $reflection->getMethod('resolvePresentedValue');
    $resolvePresented->setAccessible(true);
    $format = $reflection->getMethod('formatRelationColumnState');
    $format->setAccessible(true);

    $presented = $resolvePresented->invoke($compiler, $field, $owner->fresh());

    expect($format->invoke($compiler, $presented))->toBe('Linked Title');
});
