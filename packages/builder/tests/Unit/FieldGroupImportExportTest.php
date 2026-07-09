<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItemResource.php';

use Illuminate\Validation\ValidationException;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Services\FieldGroupExporter;
use Moox\Builder\Services\FieldGroupImporter;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Support\FieldGroupExportSchema;
use Moox\Builder\Tests\Support\TestItemResource;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

it('exports and imports a field group definition with fields', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Content',
        'slug' => 'content-export',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'placement' => 'main',
        'settings' => ['columns' => 2, 'visible_admin' => true],
        'sort' => 5,
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Content',
        'slug' => 'content-export',
        'active' => true,
        'sort' => 5,
        'placement' => 'main',
        'settings' => ['columns' => 2, 'visible_admin' => true],
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'headline',
                'label' => 'Headline',
                'type' => 'text',
                'required' => true,
                'config' => ['maxLength' => 120],
                'settings' => ['visible_api' => true],
            ],
            [
                'name' => 'category',
                'label' => 'Category',
                'type' => 'select',
                'required' => false,
                'options' => [
                    ['label' => 'News', 'value' => 'news'],
                    ['label' => 'Blog', 'value' => 'blog'],
                ],
            ],
        ],
    ]);

    $payload = app(FieldGroupExporter::class)->export($group->fresh());

    expect($payload)
        ->toMatchArray([
            'schema' => FieldGroupExportSchema::SCHEMA,
            'version' => FieldGroupExportSchema::VERSION,
        ])
        ->and($payload['group']['slug'])->toBe('content-export')
        ->and($payload['group']['sort'])->toBe(5)
        ->and($payload['group']['fields'])->toHaveCount(2);

    FieldGroup::query()->whereKey($group->getKey())->delete();

    $imported = app(FieldGroupImporter::class)->import($payload);

    expect($imported->slug)->toBe('content-export')
        ->and($imported->sort)->toBe(5)
        ->and($imported->settings)->toMatchArray(['columns' => 2, 'visible_admin' => true])
        ->and($imported->fields()->whereNull('parent_field_id')->pluck('name')->all())
        ->toBe(['headline', 'category']);

    $headline = Field::query()->where('field_group_id', $imported->getKey())->where('name', 'headline')->first();

    expect($headline?->validation['required'] ?? null)->toBeTrue()
        ->and($headline?->config)->toMatchArray(['maxLength' => 120]);
});

it('imports definitions with explicit locale translations in the payload', function (): void {
    config()->set('builder.default_locale', 'en_US');

    $payload = [
        'schema' => FieldGroupExportSchema::SCHEMA,
        'version' => FieldGroupExportSchema::VERSION,
        'exportedAt' => now()->toIso8601String(),
        'group' => [
            'name' => 'Content EN',
            'slug' => 'localized-import',
            'placement' => 'main',
            'locationRules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
            'settings' => [],
            'translations' => [
                'en_US' => ['name' => 'Content EN'],
                'de_CH' => ['name' => 'Inhalt DE'],
            ],
            'fields' => [
                [
                    'name' => 'title',
                    'label' => 'Title EN',
                    'type' => 'text',
                    'sort' => 0,
                    'config' => [],
                    'validation' => ['required' => false, 'rules' => []],
                    'settings' => [],
                    'options' => [],
                    'children' => [],
                    'translations' => [
                        'en_US' => ['label' => 'Title EN', 'config' => []],
                        'de_CH' => ['label' => 'Titel DE', 'config' => []],
                    ],
                ],
            ],
            'active' => true,
            'sort' => 0,
        ],
    ];

    $imported = app(FieldGroupImporter::class)->import($payload);

    expect($imported->name)->toBe('Content EN')
        ->and($imported->translate('de_CH')?->name)->toBe('Inhalt DE')
        ->and($imported->fields()->first()?->translate('de_CH')?->label)->toBe('Titel DE');
});

it('imports builder persistence-shaped payloads into canonical definitions', function (): void {
    $payload = [
        'schema' => FieldGroupExportSchema::SCHEMA,
        'version' => FieldGroupExportSchema::VERSION,
        'exportedAt' => now()->toIso8601String(),
        'group' => [
            'name' => 'Compat import',
            'slug' => 'compat-import',
            'placement' => 'main',
            'target_entities' => ['item'],
            'settings' => [],
            'translations' => [],
            'fields' => [
                [
                    'name' => 'headline',
                    'label' => 'Headline',
                    'type' => 'text',
                    'required' => true,
                    'validation' => [
                        'rule_rows' => [
                            ['rule' => 'min', 'value' => '3'],
                        ],
                        'raw_rules' => 'starts_with:foo',
                    ],
                    'settings' => [],
                    'options' => [],
                ],
                [
                    'name' => 'content_blocks',
                    'label' => 'Content blocks',
                    'type' => 'flexible_content',
                    'settings' => [],
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
                                    'settings' => [],
                                    'options' => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'active' => true,
            'sort' => 0,
        ],
    ];

    $imported = app(FieldGroupImporter::class)->import($payload);

    expect($imported->location_rules)->toBe([
        [[
            'param' => 'entity',
            'operator' => '==',
            'value' => 'item',
        ]],
    ]);

    $headline = $imported->fields()->where('name', 'headline')->first();
    $contentBlocks = $imported->fields()->where('name', 'content_blocks')->first();
    $heroLayout = $contentBlocks?->children()->where('type', 'flexible_layout')->where('name', 'hero')->first();

    expect($headline?->validation)->toBe([
        'required' => true,
        'rules' => ['min:3', 'starts_with:foo'],
    ])->and($heroLayout)->not->toBeNull()
        ->and($heroLayout?->children()->where('name', 'title')->exists())->toBeTrue();
});

it('generates duplicate slugs for copies', function (): void {
    FieldGroup::query()->create([
        'name' => 'Original',
        'slug' => 'test',
        'location_rules' => [],
        'active' => true,
    ]);

    expect(app(FieldGroupImporter::class)->duplicateSlug('test'))->toBe('test-copy');

    FieldGroup::query()->create([
        'name' => 'Copy',
        'slug' => 'test-copy',
        'location_rules' => [],
        'active' => true,
    ]);

    expect(app(FieldGroupImporter::class)->duplicateSlug('test'))->toBe('test-2');
});

it('imports as a duplicate slug without replacing the original group', function (): void {
    FieldGroup::query()->create([
        'name' => 'Original',
        'slug' => 'test',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync(
        FieldGroup::query()->where('slug', 'test')->firstOrFail(),
        [
            'name' => 'Original',
            'slug' => 'test',
            'active' => true,
            'sort' => 0,
            'placement' => 'main',
            'target_entities' => ['item'],
            'fields' => [
                [
                    'name' => 'buttongroup',
                    'label' => 'Button group',
                    'type' => 'button_group',
                    'required' => false,
                    'options' => [
                        ['label' => 'One', 'value' => 'one'],
                    ],
                ],
            ],
        ],
    );

    $payload = [
        'schema' => FieldGroupExportSchema::SCHEMA,
        'version' => FieldGroupExportSchema::VERSION,
        'exportedAt' => now()->toIso8601String(),
        'group' => [
            'name' => 'Copy',
            'slug' => 'test',
            'placement' => 'main',
            'locationRules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
            'settings' => [],
            'translations' => [],
            'fields' => [
                [
                    'name' => 'buttongroup',
                    'label' => 'Button group',
                    'type' => 'button_group',
                    'sort' => 0,
                    'config' => [],
                    'validation' => ['required' => false, 'rules' => []],
                    'settings' => [],
                    'options' => [
                        ['label' => 'One', 'value' => 'one'],
                    ],
                    'children' => [],
                    'translations' => [],
                ],
            ],
            'active' => true,
            'sort' => 0,
        ],
    ];

    $importer = app(FieldGroupImporter::class);

    $imported = $importer->import(
        $payload,
        slugOverride: $importer->duplicateSlug('test'),
    );

    expect($imported->slug)->toBe('test-copy')
        ->and($imported->location_rules)->toBe([])
        ->and(FieldGroup::query()->where('slug', 'test')->exists())->toBeTrue()
        ->and(FieldGroup::query()->where('slug', 'test-copy')->exists())->toBeTrue()
        ->and($imported->fields()->where('name', 'buttongroup')->exists())->toBeTrue();
});

it('rejects imports when slug exists unless replace is enabled', function (): void {
    FieldGroup::query()->create([
        'name' => 'Existing',
        'slug' => 'duplicate-slug',
        'location_rules' => [],
        'active' => true,
    ]);

    $payload = [
        'schema' => FieldGroupExportSchema::SCHEMA,
        'version' => FieldGroupExportSchema::VERSION,
        'exportedAt' => now()->toIso8601String(),
        'group' => [
            'name' => 'Imported',
            'slug' => 'duplicate-slug',
            'placement' => 'main',
            'locationRules' => [],
            'settings' => [],
            'translations' => [],
            'fields' => [],
            'active' => true,
            'sort' => 0,
        ],
    ];

    try {
        app(FieldGroupImporter::class)->import($payload);
        expect(false)->toBeTrue('Expected import to fail for duplicate slug');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('file');
    }

    $imported = app(FieldGroupImporter::class)->import($payload, replaceExisting: true);

    expect($imported->name)->toBe('Imported')
        ->and(FieldGroup::query()->where('slug', 'duplicate-slug')->count())->toBe(1);
});

it('rejects invalid export payloads', function (): void {
    expect(fn () => app(FieldGroupImporter::class)->importFromJson('{not-json'))
        ->toThrow(ValidationException::class);

    expect(fn () => app(FieldGroupImporter::class)->import([
        'schema' => 'other',
        'version' => 1,
        'group' => [],
    ]))->toThrow(ValidationException::class);
});
