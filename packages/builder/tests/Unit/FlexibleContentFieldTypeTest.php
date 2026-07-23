<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';

use Filament\Forms\Components\Builder;
use Illuminate\Validation\ValidationException;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Registry\FieldTypeRegistry;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Services\FieldValueValidator;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->createItemsTable();
});

it('syncs flexible content layouts and nested subfields', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Flexible',
        'slug' => 'flexible',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Flexible',
        'slug' => 'flexible',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'content',
                'label' => 'Content',
                'type' => 'flexible_content',
                'required' => false,
                'layouts' => [
                    [
                        'name' => 'hero',
                        'label' => 'Hero',
                        'children' => [
                            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                        ],
                    ],
                    [
                        'name' => 'text',
                        'label' => 'Text',
                        'children' => [
                            ['name' => 'body', 'label' => 'Body', 'type' => 'textarea'],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $group->load(['fields.children.children']);

    $content = $group->fields->firstWhere('name', 'content');

    expect($content)->not->toBeNull()
        ->and($content->children)->toHaveCount(2)
        ->and($content->children->first()->type)->toBe('flexible_layout')
        ->and($content->children->first()->children)->toHaveCount(1);
});

it('compiles flexible content as a filament builder with layout blocks', function (): void {
    $field = new FieldDefinition(
        name: 'content',
        label: 'Content',
        type: 'flexible_content',
        children: collect([
            new FieldDefinition(
                name: 'hero',
                label: 'Hero',
                type: 'flexible_layout',
                children: collect([
                    new FieldDefinition(name: 'title', label: 'Title', type: 'text'),
                ]),
            ),
            new FieldDefinition(
                name: 'text',
                label: 'Text',
                type: 'flexible_layout',
                children: collect([
                    new FieldDefinition(name: 'body', label: 'Body', type: 'textarea'),
                ]),
            ),
        ]),
    );

    $component = app(FieldTypeRegistry::class)
        ->get('flexible_content')
        ->formComponent($field);

    expect($component)->toBeInstanceOf(Builder::class);
});

it('normalizes stored flexible content as a list for filament builder hydration', function (): void {
    $fieldType = app(FieldTypeRegistry::class)->get('flexible_content');

    $normalized = $fieldType->normalizeForForm([
        ['type' => 'hero', 'data' => ['title' => 'Hello']],
        ['type' => 'text', 'data' => ['body' => 'World']],
    ]);

    expect($normalized)->toHaveCount(2)
        ->and(array_is_list($normalized))->toBeTrue()
        ->and($normalized[0])->toMatchArray(['type' => 'hero', 'data' => ['title' => 'Hello']])
        ->and($normalized[1])->toMatchArray(['type' => 'text', 'data' => ['body' => 'World']]);
});

it('persists and loads flexible content values', function (): void {
    $record = TestItem::query()->create(['title' => 'Flexible test']);
    $manager = app(CustomFieldsManager::class);

    $field = new FieldDefinition(
        name: 'content',
        label: 'Content',
        type: 'flexible_content',
        children: collect([
            new FieldDefinition(
                name: 'hero',
                label: 'Hero',
                type: 'flexible_layout',
                children: collect([
                    new FieldDefinition(name: 'title', label: 'Title', type: 'text'),
                ]),
            ),
        ]),
    );

    $value = [
        ['type' => 'hero', 'data' => ['title' => 'Hello']],
    ];

    $manager->saveValues('item', $record, ['content' => $value], collect([$field]));

    $loaded = $manager->loadValues('item', $record, collect([$field]));

    expect($loaded['content'])->toBe([
        ['type' => 'hero', 'data' => ['title' => 'Hello']],
    ]);
});

it('rejects empty flexible content blocks', function (): void {
    $field = new FieldDefinition(
        name: 'content',
        label: 'Content',
        type: 'flexible_content',
        children: collect([
            new FieldDefinition(
                name: 'hero',
                label: 'Hero',
                type: 'flexible_layout',
                children: collect([
                    new FieldDefinition(
                        name: 'title',
                        label: 'Title',
                        type: 'text',
                        validation: ['required' => true, 'rules' => []],
                    ),
                ]),
            ),
        ]),
    );

    expect(fn () => app(FieldValueValidator::class)->assertValid($field, [
        ['type' => 'hero', 'data' => ['title' => '']],
    ]))->toThrow(ValidationException::class);
});

it('persists default values configured on flexible layout subfields', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Flexible defaults',
        'slug' => 'flexible-defaults',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Flexible defaults',
        'slug' => 'flexible-defaults',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'content',
                'label' => 'Content',
                'type' => 'flexible_content',
                'required' => false,
                'layouts' => [
                    [
                        'name' => 'hero',
                        'label' => 'Hero',
                        'children' => [
                            [
                                'name' => 'title',
                                'label' => 'Title',
                                'type' => 'text',
                                'required' => false,
                                'config' => ['default' => 'Hero default'],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $group->load(['fields.children.children']);

    $title = $group->fields->firstWhere('name', 'content')
        ?->children->firstWhere('name', 'hero')
        ?->children->firstWhere('name', 'title');

    expect($title)->not->toBeNull()
        ->and($title->config['default'] ?? null)->toBe('Hero default');
});
