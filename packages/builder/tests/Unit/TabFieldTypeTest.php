<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';

use Moox\Builder\Compiler\SchemaCompiler;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Data\FieldGroupDefinition;
use Moox\Builder\Data\LocationContext;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Services\TabStructureMigrator;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

it('compiles tab fields with nested children into filament tabs', function (): void {
    $group = new FieldGroupDefinition(
        name: 'Fahrzeugdaten',
        slug: 'fahrzeugdaten',
        placement: 'default',
        fields: collect([
            new FieldDefinition(
                name: 'tab-allgemein',
                label: 'Allgemein',
                type: 'tab',
                children: collect([
                    new FieldDefinition(
                        name: 'modell',
                        label: 'Modell',
                        type: 'text',
                        validation: ['required' => true],
                    ),
                    new FieldDefinition(name: 'preis', label: 'Preis', type: 'number', config: ['min' => 0]),
                ]),
            ),
            new FieldDefinition(
                name: 'tab-technik',
                label: 'Technik',
                type: 'tab',
                children: collect([
                    new FieldDefinition(name: 'ps', label: 'PS', type: 'number'),
                ]),
            ),
        ]),
    );

    $compiler = app(SchemaCompiler::class);
    $sections = $compiler->compile(collect([$group]));

    expect($sections)->toHaveCount(1)
        ->and($compiler->rules(collect([$group])))->toHaveKeys(['modell', 'preis']);
});

it('syncs tab fields with nested children', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Tabs',
        'slug' => 'tabs',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Tabs',
        'slug' => 'tabs',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'tab-one',
                'label' => 'One',
                'type' => 'tab',
                'children' => [
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => false],
                ],
            ],
        ],
    ]);

    $group->load(['fields.children']);

    $tab = $group->fields->firstWhere('name', 'tab-one');

    expect($tab)->not->toBeNull()
        ->and($tab->children)->toHaveCount(1)
        ->and($tab->children->first()->name)->toBe('title');
});

it('loads flexible layout children when nested inside a tab', function (): void {
    app(FieldGroupPersistence::class)->sync(
        FieldGroup::query()->create([
            'name' => 'Fahrzeugdaten',
            'slug' => 'fahrzeugdaten-nested-flex',
            'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
            'active' => true,
        ]),
        [
            'name' => 'Fahrzeugdaten',
            'slug' => 'fahrzeugdaten-nested-flex',
            'active' => true,
            'sort' => 0,
            'target_entities' => ['item'],
            'fields' => [
                [
                    'name' => 'tab-inserat',
                    'label' => 'Inserat',
                    'type' => 'tab',
                    'children' => [
                        [
                            'name' => 'inserat-inhalt',
                            'label' => 'Inserat-Inhalt',
                            'type' => 'flexible_content',
                            'layouts' => [
                                [
                                    'name' => 'hero',
                                    'label' => 'Hero',
                                    'children' => [
                                        ['name' => 'titel', 'label' => 'Titel', 'type' => 'text', 'required' => false],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    );

    app(DefinitionRegistry::class)->forget();

    $groups = app(DefinitionRegistry::class)->fieldGroupsFor(
        new LocationContext('item'),
    );

    $flex = $groups
        ->firstWhere('slug', 'fahrzeugdaten-nested-flex')
        ?->fields
        ->firstWhere('name', 'tab-inserat')
        ?->children
        ->firstWhere('name', 'inserat-inhalt');

    expect($flex)->not->toBeNull()
        ->and($flex->layouts()->first()?->children)->toHaveCount(1)
        ->and($flex->layouts()->first()?->children->first()?->name)->toBe('titel');
});

it('migrates legacy flat tab markers into tab children', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Legacy',
        'slug' => 'legacy',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    $tab = $group->fields()->create([
        'name' => 'tab-old',
        'label' => 'Old tab',
        'type' => 'tab',
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    $group->fields()->create([
        'name' => 'legacy-field',
        'label' => 'Legacy field',
        'type' => 'text',
        'sort' => 1,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    expect(app(TabStructureMigrator::class)->migrateGroup($group->fresh()))->toBeTrue();

    $tab->refresh()->load('children');

    expect($tab->children)->toHaveCount(1)
        ->and($tab->children->first()->name)->toBe('legacy-field');
});
