<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/TestItemResource.php';

use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Cache;
use Moox\Builder\Data\FieldGroupDefinition;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Support\FieldGroupPlacement;
use Moox\Builder\Tests\Support\TestItemResource;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

it('normalizes unknown and legacy placements to main', function (): void {
    expect(FieldGroupPlacement::normalize(null))->toBe('main')
        ->and(FieldGroupPlacement::normalize('default'))->toBe('main')
        ->and(FieldGroupPlacement::normalize('bogus'))->toBe('main')
        ->and(FieldGroupPlacement::normalize('sidebar'))->toBe('sidebar');
});

it('matches the legacy default placement against main on the definition', function (): void {
    $legacy = FieldGroupDefinition::fromArray(['name' => 'G', 'slug' => 'g', 'placement' => 'default', 'fields' => []]);
    $sidebar = FieldGroupDefinition::fromArray(['name' => 'S', 'slug' => 's', 'placement' => 'sidebar', 'fields' => []]);

    expect($legacy->hasPlacement('main'))->toBeTrue()
        ->and($legacy->hasPlacement('sidebar'))->toBeFalse()
        ->and($sidebar->hasPlacement('sidebar'))->toBeTrue()
        ->and($sidebar->hasPlacement('main'))->toBeFalse();
});

it('splits compiled form sections by placement', function (): void {
    FieldGroup::query()->delete();
    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $main = FieldGroup::query()->create([
        'name' => 'Vehicle data',
        'slug' => 'vehicle-data',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'placement' => 'default',
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $main->getKey(),
        'name' => 'model',
        'label' => 'Model',
        'type' => 'text',
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    $sidebar = FieldGroup::query()->create([
        'name' => 'Internal notes',
        'slug' => 'internal-notes',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'placement' => 'sidebar',
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $sidebar->getKey(),
        'name' => 'note',
        'label' => 'Note',
        'type' => 'textarea',
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $mainSections = TestItemResource::customFieldComponents();
    $sidebarSections = TestItemResource::customFieldComponents('sidebar');

    expect($mainSections)->toHaveCount(1)
        ->and($mainSections[0])->toBeInstanceOf(Section::class)
        ->and($mainSections[0]->getHeading())->toBe('Vehicle data')
        ->and($sidebarSections)->toHaveCount(1)
        ->and($sidebarSections[0])->toBeInstanceOf(Section::class)
        ->and($sidebarSections[0]->getHeading())->toBe('Internal notes');
});

it('includes all placements in table columns regardless of placement', function (): void {
    FieldGroup::query()->delete();
    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $sidebar = FieldGroup::query()->create([
        'name' => 'Internal notes',
        'slug' => 'internal-notes',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'placement' => 'sidebar',
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $sidebar->getKey(),
        'name' => 'note',
        'label' => 'Note',
        'type' => 'text',
        'sort' => 0,
        'settings' => ['show_in_table' => true],
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);

    expect(TestItemResource::customFieldColumns())->toHaveCount(1);
});
