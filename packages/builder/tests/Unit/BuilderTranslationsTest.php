<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/TestTranslatableItem.php';

use Illuminate\Support\Facades\Cache;
use Moox\Builder\Data\LocationContext;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Support\BuilderLocaleResolver;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\Support\TestTranslatableItem;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->createItemsTable();

    FieldGroup::query()->delete();

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();
});

it('stores field values per locale for translatable entities', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Basics',
        'slug' => 'basics',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'translatable-item']]],
        'active' => true,
    ]);

    $group->translateOrNew(app(BuilderLocaleResolver::class)->defaultLocale())->name = 'Basics';
    $group->saveTranslations();

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'color',
        'label' => 'Color',
        'type' => 'text',
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    $record = TestTranslatableItem::query()->create(['title' => 'Demo']);

    request()->merge(['lang' => 'de_CH']);

    app(CustomFieldsManager::class)->saveValues('translatable-item', $record, [
        'color' => 'Blau',
    ], app(CustomFieldsManager::class)->fieldsForEntity('translatable-item'));

    request()->merge(['lang' => 'en_US']);

    app(CustomFieldsManager::class)->saveValues('translatable-item', $record, [
        'color' => 'Blue',
    ], app(CustomFieldsManager::class)->fieldsForEntity('translatable-item'));

    expect(FieldValue::query()->count())->toBe(2)
        ->and(FieldValue::query()->where('locale', 'de_CH')->value('value_string'))->toBe('Blau')
        ->and(FieldValue::query()->where('locale', 'en_US')->value('value_string'))->toBe('Blue');
});

it('resolves localized field labels from translation tables', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Basics',
        'slug' => 'basics',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    $group->translateOrNew('en_US')->name = 'Basics';
    $group->translateOrNew('de_CH')->name = 'Grundlagen';
    $group->saveTranslations();

    $field = Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'color',
        'label' => 'Color',
        'type' => 'text',
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    $field->translateOrNew('en_US')->label = 'Color';
    $field->translateOrNew('de_CH')->label = 'Farbe';
    $field->saveTranslations();

    Cache::forget(DefinitionRegistry::CACHE_KEY);

    request()->merge(['lang' => 'de_CH']);

    $definition = app(DefinitionRegistry::class)
        ->fieldGroupsFor(new LocationContext('item'))
        ->first();

    expect($definition)->not->toBeNull()
        ->and($definition->name)->toBe('Grundlagen')
        ->and($definition->fields->first()?->label)->toBe('Farbe');
});

it('updates structural config on a non-default locale while keeping translatable fallbacks', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Basics',
        'slug' => 'basics',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    $baseData = [
        'name' => 'Basics',
        'slug' => 'basics',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'note',
                'label' => 'Note',
                'type' => 'text',
                'required' => false,
                'config' => ['maxLength' => 100, 'helperText' => 'English helper'],
            ],
        ],
    ];

    app(FieldGroupPersistence::class)->sync($group, $baseData);

    request()->merge(['lang' => 'de_CH']);

    $deData = $baseData;
    $deData['fields'][0]['config'] = ['maxLength' => 250, 'helperText' => 'Deutscher Hilfetext'];

    app(FieldGroupPersistence::class)->sync($group->fresh(), $deData);

    $config = Field::query()->where('name', 'note')->value('config');

    expect($config['maxLength'])->toBe(250)
        ->and($config['helperText'])->toBe('English helper');
});

it('resolves localized group names for admin list locale', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Basics',
        'slug' => 'basics',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    $group->translateOrNew('en_US')->name = 'Basics';
    $group->translateOrNew('de_CH')->name = 'Grundlagen';
    $group->saveTranslations();

    request()->merge(['lang' => 'de_CH']);

    $name = app(FieldGroupPersistence::class)
        ->localizedGroupName($group->fresh('translations'), 'de_CH');

    expect($name)->toBe('Grundlagen');
});

it('falls back to config default locale when localization table is missing', function (): void {
    expect(app(BuilderLocaleResolver::class)->defaultLocale())->toBe('en_US')
        ->and(app(BuilderLocaleResolver::class)->adminDefaultLocale())->toBe('en_US');
});

it('falls back to default locale field values when translation is missing on non-translatable entities', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Basics',
        'slug' => 'basics',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'color',
        'label' => 'Color',
        'type' => 'text',
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    $record = TestItem::query()->create(['title' => 'Demo']);

    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => $record->getKey(),
        'field_name' => 'color',
        'locale' => app(BuilderLocaleResolver::class)->defaultLocale(),
        'value_string' => 'Blue',
    ]);

    request()->merge(['lang' => 'de_CH']);

    expect($record->customFields(fresh: true)['color'])->toBe('Blue');
});
