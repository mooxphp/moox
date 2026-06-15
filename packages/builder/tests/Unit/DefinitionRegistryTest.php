<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';

uses(Moox\Builder\Tests\TestCase::class);

use Illuminate\Support\Facades\Cache;
use Moox\Builder\Data\LocationContext;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Tests\TestCase;

it('caches definitions and invalidates on save', function (): void {
    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $group = FieldGroup::query()->create([
        'name' => 'Demo',
        'slug' => 'demo',
        'location_rules' => [
            [['param' => 'entity', 'operator' => '==', 'value' => 'item']],
        ],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'title',
        'label' => 'Title',
        'type' => 'text',
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    $registry = app(DefinitionRegistry::class);

    expect(Cache::has(DefinitionRegistry::CACHE_KEY))->toBeFalse();

    $groups = $registry->fieldGroupsFor(new LocationContext('item'));
    expect($groups)->toHaveCount(1)
        ->and(Cache::has(DefinitionRegistry::CACHE_KEY))->toBeTrue();

    $group->update(['name' => 'Updated']);

    expect(Cache::has(DefinitionRegistry::CACHE_KEY))->toBeFalse();
});

it('hydrates definitions from cached arrays', function (): void {
    Cache::forget(DefinitionRegistry::CACHE_KEY);

    FieldGroup::query()->create([
        'name' => 'Cached',
        'slug' => 'cached',
        'location_rules' => [
            [['param' => 'entity', 'operator' => '==', 'value' => 'item']],
        ],
        'active' => true,
    ]);

    $registry = app(DefinitionRegistry::class);
    $registry->fieldGroupsFor(new LocationContext('item'));

    $cached = Cache::get(DefinitionRegistry::CACHE_KEY);
    expect($cached)->toBeArray()
        ->and($cached[0])->toBeArray()
        ->and($cached[0]['name'])->toBe('Cached');

    $groups = $registry->fieldGroupsFor(new LocationContext('item'));
    expect($groups)->toHaveCount(1)
        ->and($groups->first()->name)->toBe('Cached');
});

it('preserves field sort order when hydrating from cache', function (): void {
    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $group = FieldGroup::query()->create([
        'name' => 'Ordered',
        'slug' => 'ordered',
        'location_rules' => [
            [['param' => 'entity', 'operator' => '==', 'value' => 'item']],
        ],
        'active' => true,
    ]);

    foreach ([
        ['name' => 'third', 'label' => 'Third', 'sort' => 2],
        ['name' => 'first', 'label' => 'First', 'sort' => 0],
        ['name' => 'second', 'label' => 'Second', 'sort' => 1],
    ] as $field) {
        Field::query()->create([
            'field_group_id' => $group->getKey(),
            'name' => $field['name'],
            'label' => $field['label'],
            'type' => 'text',
            'sort' => $field['sort'],
            'validation' => ['required' => false, 'rules' => []],
        ]);
    }

    $registry = app(DefinitionRegistry::class);
    $registry->fieldGroupsFor(new LocationContext('item'));

    $groups = $registry->fieldGroupsFor(new LocationContext('item'));

    expect($groups->first()->fields->pluck('name')->all())->toBe(['first', 'second', 'third']);
});
