<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/TestItemResource.php';

use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Cache;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Data\FieldGroupDefinition;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Support\FieldWidth;
use Moox\Builder\Support\StorableFieldCollector;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\Support\TestItemResource;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->createItemsTable();
    Cache::forget(DefinitionRegistry::CACHE_KEY);
    FieldGroup::query()->delete();
});

it('maps width fractions to column spans on a 12 grid', function (): void {
    expect(FieldWidth::columnSpan('full'))->toBe(12)
        ->and(FieldWidth::columnSpan('1/2'))->toBe(6)
        ->and(FieldWidth::columnSpan('1/3'))->toBe(4)
        ->and(FieldWidth::columnSpan('2/3'))->toBe(8)
        ->and(FieldWidth::columnSpan('1/4'))->toBe(3)
        ->and(FieldWidth::columnSpan('3/4'))->toBe(9)
        ->and(FieldWidth::columnSpan(null))->toBe(12)
        ->and(FieldWidth::columnSpan('bogus'))->toBe(12);
});

it('reads width and column span from the field definition settings', function (): void {
    $default = FieldDefinition::fromArray(['name' => 'a', 'label' => 'A', 'type' => 'text']);
    $half = FieldDefinition::fromArray(['name' => 'b', 'label' => 'B', 'type' => 'text', 'settings' => ['width' => '1/2']]);

    expect($default->width())->toBe('full')
        ->and($default->columnSpan())->toBe(12)
        ->and($half->width())->toBe('1/2')
        ->and($half->columnSpan())->toBe(6);
});

it('applies the configured width as a column span on compiled components', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Layout',
        'slug' => 'layout-width',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Layout',
        'slug' => 'layout-width',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            ['name' => 'first-name', 'label' => 'First name', 'type' => 'text', 'required' => false, 'settings' => ['width' => '1/2']],
            ['name' => 'last-name', 'label' => 'Last name', 'type' => 'text', 'required' => false, 'settings' => ['width' => '1/2']],
        ],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $section = TestItemResource::customFieldComponents()[0];
    $first = collect($section->getDefaultChildComponents())
        ->first(fn ($component): bool => $component->getName() === 'first-name');

    expect(normalizeSpan($section->getColumns()))->toBe(12)
        ->and(normalizeSpan($first->getColumnSpan()))->toBe(6);
});

it('maps group column counts to default field spans', function (): void {
    expect(FieldWidth::columnsToSpan(1))->toBe(12)
        ->and(FieldWidth::columnsToSpan(2))->toBe(6)
        ->and(FieldWidth::columnsToSpan(3))->toBe(4)
        ->and(FieldWidth::columnsToSpan(4))->toBe(3)
        ->and(FieldWidth::columnsToSpan(null))->toBe(12)
        ->and(FieldWidth::columnsToSpan(7))->toBe(12);
});

it('reads columns and default span from the group definition', function (): void {
    $single = FieldGroupDefinition::fromArray([
        'name' => 'A', 'slug' => 'a', 'placement' => 'main',
    ]);
    $twoCol = FieldGroupDefinition::fromArray([
        'name' => 'B', 'slug' => 'b', 'placement' => 'main', 'settings' => ['columns' => 2],
    ]);

    expect($single->columns())->toBe(1)
        ->and($single->defaultColumnSpan())->toBe(12)
        ->and($twoCol->columns())->toBe(2)
        ->and($twoCol->defaultColumnSpan())->toBe(6);
});

it('flows auto-width fields into the group column layout', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Grid',
        'slug' => 'grid-columns',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Grid',
        'slug' => 'grid-columns',
        'active' => true,
        'sort' => 0,
        'settings' => ['columns' => 2],
        'target_entities' => ['item'],
        'fields' => [
            ['name' => 'street', 'label' => 'Street', 'type' => 'text', 'required' => false, 'settings' => ['width' => 'auto']],
            ['name' => 'city', 'label' => 'City', 'type' => 'text', 'required' => false],
            ['name' => 'country', 'label' => 'Country', 'type' => 'text', 'required' => false, 'settings' => ['width' => '1/3']],
        ],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $section = TestItemResource::customFieldComponents()[0];
    $children = collect($section->getDefaultChildComponents());
    $span = fn (string $name): int|string|null => normalizeSpan(
        $children->first(fn ($component): bool => $component->getName() === $name)->getColumnSpan(),
    );

    expect($span('street'))->toBe(6)
        ->and($span('city'))->toBe(6)
        ->and($span('country'))->toBe(4);
});

it('round trips width through field group persistence', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Widths',
        'slug' => 'widths',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    $persistence = app(FieldGroupPersistence::class);

    $persistence->sync($group, [
        'name' => 'Widths',
        'slug' => 'widths',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            ['name' => 'price', 'label' => 'Price', 'type' => 'number', 'required' => false, 'settings' => ['width' => '1/3']],
        ],
    ]);

    expect($group->fields()->where('name', 'price')->first()->settings['width'])->toBe('1/3')
        ->and($persistence->fieldRowsForForm($group->fresh())[0]['settings']['width'])->toBe('1/3');
});

it('compiles a section marker as a wrapping section with flat children', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Sectioned',
        'slug' => 'sectioned',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Sectioned',
        'slug' => 'sectioned',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'internal',
                'label' => 'Internal',
                'type' => 'section',
                'required' => false,
                'children' => [
                    ['name' => 'note', 'label' => 'Note', 'type' => 'text', 'required' => false],
                ],
            ],
        ],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $rootSection = TestItemResource::customFieldComponents()[0];
    $sectionMarker = collect($rootSection->getDefaultChildComponents())
        ->first(fn ($component): bool => $component instanceof Section);

    expect($sectionMarker)->not->toBeNull()
        ->and($sectionMarker->getHeading())->toBe('Internal')
        ->and(collect($sectionMarker->getDefaultChildComponents())->first()->getName())->toBe('note');
});

it('flattens section children into storable fields and top-level rules', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Sectioned rules',
        'slug' => 'sectioned-rules',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Sectioned rules',
        'slug' => 'sectioned-rules',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'panel',
                'label' => 'Panel',
                'type' => 'section',
                'required' => false,
                'children' => [
                    ['name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => true],
                ],
            ],
        ],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $storableNames = app(CustomFieldsManager::class)->fieldsForEntity('item')->pluck('name')->all();
    $rules = TestItemResource::customFieldRules();

    expect($storableNames)->toBe(['headline'])
        ->and($rules)->toHaveKey('headline')
        ->and($rules['headline'])->toContain('required');
});

it('stores section child values flat at the top level', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);
    $manager = app(CustomFieldsManager::class);

    $sectionField = new FieldDefinition(
        name: 'panel',
        label: 'Panel',
        type: 'section',
        children: collect([
            new FieldDefinition(name: 'headline', label: 'Headline', type: 'text'),
        ]),
    );

    $storable = app(StorableFieldCollector::class)->definitionsFromList(collect([$sectionField]));

    $manager->saveValues('item', $record, ['headline' => 'Hello'], $storable);

    expect($storable->pluck('name')->all())->toBe(['headline'])
        ->and($manager->loadValues('item', $record, $storable))->toBe(['headline' => 'Hello']);
});

/**
 * Filament stores spans per breakpoint (e.g. ['default' => 1, 'lg' => 6]); the
 * configured value lives on the lg breakpoint.
 *
 * @param  array<string, int|string|null>|int|string|null  $span
 */
function normalizeSpan(array|int|string|null $span): int|string|null
{
    if (! is_array($span)) {
        return $span;
    }

    return $span['lg'] ?? $span['default'] ?? (array_values($span)[0] ?? null);
}
