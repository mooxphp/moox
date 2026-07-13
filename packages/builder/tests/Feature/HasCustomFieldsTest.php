<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/TestItemResource.php';

use Filament\Resources\Events\RecordSaved;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Support\BuilderLocaleResolver;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\Support\TestItemResource;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->createItemsTable();

    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $group = FieldGroup::query()->create([
        'name' => 'Vehicle data',
        'slug' => 'vehicle-data',
        'location_rules' => [
            [['param' => 'entity', 'operator' => '==', 'value' => 'item']],
        ],
        'active' => true,
    ]);

    foreach ([
        ['name' => 'fahrzeugtyp-modell', 'label' => 'Model', 'type' => 'text'],
        ['name' => 'bruttolistenpreis', 'label' => 'Price', 'type' => 'number'],
    ] as $index => $field) {
        Field::query()->create([
            'field_group_id' => $group->getKey(),
            ...$field,
            'sort' => $index,
            'validation' => ['required' => false, 'rules' => []],
        ]);
    }
});

it('exposes compiled custom field components for matching resources', function (): void {
    $components = TestItemResource::customFieldComponents();

    expect($components)->toHaveCount(1)
        ->and($components[0])->toBeInstanceOf(Section::class);
});

it('exposes compiled custom field filters for matching resources', function (): void {
    Field::query()->create([
        'field_group_id' => FieldGroup::query()->first()->getKey(),
        'name' => 'fuel',
        'label' => 'Fuel',
        'type' => 'select',
        'settings' => ['show_in_filter' => true],
        'sort' => 2,
        'validation' => ['required' => false, 'rules' => []],
    ])->options()->create([
        'label' => 'Petrol',
        'value' => 'petrol',
        'sort' => 0,
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();

    expect(TestItemResource::customFieldFilters())->toHaveCount(1);
});

it('returns no components when no field groups match the entity', function (): void {
    FieldGroup::query()->delete();

    expect(TestItemResource::customFieldComponents())->toBe([]);
});

it('loads and saves custom field values for a record', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);

    $manager = app(CustomFieldsManager::class);

    $manager->saveFromFormData(TestItemResource::class, $record, [
        'fahrzeugtyp-modell' => 'Golf GTI',
        'bruttolistenpreis' => '32990',
    ]);

    $values = FieldValue::query()
        ->forRecord('item', $record->getKey())
        ->get()
        ->keyBy('field_name');

    expect($values['fahrzeugtyp-modell']->value_string)->toBe('Golf GTI')
        ->and((float) $values['bruttolistenpreis']->value_decimal)->toBe(32990.0);

    $loaded = $manager->loadFormData(TestItemResource::class, $record);

    expect($loaded)->toMatchArray([
        'fahrzeugtyp-modell' => 'Golf GTI',
        'bruttolistenpreis' => 32990,
    ]);
});

it('persists custom fields when filament dispatches record saved', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);

    session([BuilderLocaleResolver::ADMIN_SESSION_KEY => 'de_CH']);
    request()->merge(['lang' => 'de_CH']);

    $page = new class extends Page
    {
        protected static string $resource = TestItemResource::class;
    };

    Event::dispatch(RecordSaved::class, [
        'record' => $record,
        'data' => [
            'fahrzeugtyp-modell' => 'Polo',
            'bruttolistenpreis' => '24990',
        ],
        'page' => $page,
    ]);

    $values = FieldValue::query()
        ->forRecord('item', $record->getKey())
        ->get()
        ->keyBy('field_name');

    expect($values['fahrzeugtyp-modell']->value_string)->toBe('Polo')
        ->and((float) $values['bruttolistenpreis']->value_decimal)->toBe(24990.0)
        ->and($values['fahrzeugtyp-modell']->locale)->toBe(app(BuilderLocaleResolver::class)->defaultLocale());
});
