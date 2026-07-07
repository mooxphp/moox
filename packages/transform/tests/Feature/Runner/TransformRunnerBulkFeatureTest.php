<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Moox\Transform\Contracts\ImportRecordPayloadReader;
use Moox\Transform\Enums\TransformExecutionMode;
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;
use Moox\Transform\Tests\Support\FakeImportRecordPayloadReader;
use Moox\Transform\Tests\Support\FakeLocaleVariantResolver;
use Tests\TestCase;

require_once dirname(__DIR__, 2).'/Support/TransformTestSupport.php';
require_once dirname(__DIR__, 2).'/Support/FakeImportRecordPayloadReader.php';
require_once dirname(__DIR__, 2).'/Support/FakeLocaleVariantResolver.php';

uses(TestCase::class);

beforeEach(function (): void {
    createTestTables();
    FakeImportRecordPayloadReader::$payloads = [];
    app()->instance(ImportRecordPayloadReader::class, new FakeImportRecordPayloadReader);
});

test('it expands api import record list items into destination rows', function (): void {
    FakeImportRecordPayloadReader::$payloads[42] = [
        ['code' => 'A-1', 'title' => 'First'],
        ['code' => 'A-2', 'title' => 'Second'],
    ];

    $definition = TransformDefinition::query()->create([
        'name' => 'import-record-expand',
        'destination_model' => TransformDummyModel::class,
        'execution_mode' => TransformExecutionMode::Expand->value,
        'destination_match' => [
            'price_label' => 'item.code',
        ],
        'source_references' => [
            [
                'source_type' => 'api_import_record',
                'record_id' => '{{context.import_record_id}}',
                'alias' => 'item',
            ],
        ],
        'field_map' => [
            'title' => 'item.title',
            'price_label' => 'item.code',
        ],
        'validation_rules' => [
            'title' => ['required', 'string'],
            'price_label' => ['required', 'string'],
        ],
        'is_active' => true,
    ]);

    $parent = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'context' => [
                'import_record_id' => 42,
            ],
        ],
    ]);

    makeRunner()->run($parent);
    $parent->refresh();

    expect($parent->status)->toBe('processed')
        ->and($parent->bulk_stats['total'] ?? null)->toBe(2)
        ->and(TransformRecord::query()->where('parent_transform_record_id', $parent->id)->count())->toBe(2)
        ->and(TransformDummyModel::query()->count())->toBe(2);
});

test('it runs bulk mode with summary stats on parent record', function (): void {
    FakeImportRecordPayloadReader::$payloads[7] = [
        ['code' => 'B-1', 'title' => 'Bulk One'],
        ['code' => 'B-2', 'title' => 'Bulk Two'],
        ['code' => 'B-3', 'title' => 'Bulk Three'],
    ];

    $definition = TransformDefinition::query()->create([
        'name' => 'import-record-bulk',
        'destination_model' => TransformDummyModel::class,
        'execution_mode' => TransformExecutionMode::Bulk->value,
        'bulk' => [
            'chunk_size' => 2,
        ],
        'destination_match' => [
            'price_label' => 'item.code',
        ],
        'source_references' => [
            [
                'source_type' => 'api_import_record',
                'record_id' => '{{context.import_record_id}}',
                'alias' => 'item',
            ],
        ],
        'field_map' => [
            'title' => 'item.title',
            'price_label' => 'item.code',
        ],
        'validation_rules' => [
            'title' => ['required', 'string'],
            'price_label' => ['required', 'string'],
        ],
        'is_active' => true,
    ]);

    $parent = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'context' => [
                'import_record_id' => 7,
            ],
        ],
    ]);

    makeRunner()->run($parent);
    $parent->refresh();

    expect($parent->status)->toBe('processed')
        ->and($parent->bulk_stats['total'] ?? null)->toBe(3)
        ->and($parent->bulk_stats['processed'] ?? null)->toBe(3)
        ->and(TransformDummyModel::query()->count())->toBe(3);
});

test('it can process bulk mode without persisting child records', function (): void {
    FakeImportRecordPayloadReader::$payloads[8] = [
        ['code' => 'NC-1', 'title' => 'No Child One'],
        ['code' => 'NC-2', 'title' => 'No Child Two'],
    ];

    $definition = TransformDefinition::query()->create([
        'name' => 'import-record-bulk-inline',
        'destination_model' => TransformDummyModel::class,
        'execution_mode' => TransformExecutionMode::Bulk->value,
        'bulk' => [
            'persist_children' => false,
            'write_strategy' => 'row',
        ],
        'destination_match' => [
            'price_label' => 'item.code',
        ],
        'source_references' => [
            [
                'source_type' => 'api_import_record',
                'record_id' => '{{context.import_record_id}}',
                'alias' => 'item',
            ],
        ],
        'field_map' => [
            'title' => 'item.title',
            'price_label' => 'item.code',
        ],
        'validation_rules' => [
            'title' => ['required', 'string'],
            'price_label' => ['required', 'string'],
        ],
        'is_active' => true,
    ]);

    $parent = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'context' => [
                'import_record_id' => 8,
            ],
        ],
    ]);

    makeRunner()->run($parent);
    $parent->refresh();

    expect($parent->status)->toBe('processed')
        ->and($parent->bulk_stats['total'] ?? null)->toBe(2)
        ->and($parent->bulk_stats['processed'] ?? null)->toBe(2)
        ->and(TransformRecord::query()->where('parent_transform_record_id', $parent->id)->count())->toBe(0)
        ->and(TransformDummyModel::query()->count())->toBe(2);
});

test('it can batch upsert simple destination models in bulk mode', function (): void {
    FakeImportRecordPayloadReader::$payloads[9] = [
        ['code' => 'BU-1', 'title' => 'Batch One'],
        ['code' => 'BU-2', 'title' => 'Batch Two'],
    ];

    $definition = TransformDefinition::query()->create([
        'name' => 'import-record-bulk-batch',
        'destination_model' => TransformDummyModel::class,
        'execution_mode' => TransformExecutionMode::Bulk->value,
        'bulk' => [
            'persist_children' => false,
            'write_strategy' => 'batch',
        ],
        'destination_match' => [
            'price_label' => 'item.code',
        ],
        'source_references' => [
            [
                'source_type' => 'api_import_record',
                'record_id' => '{{context.import_record_id}}',
                'alias' => 'item',
            ],
        ],
        'field_map' => [
            'title' => 'item.title',
            'price_label' => 'item.code',
        ],
        'validation_rules' => [
            'title' => ['required', 'string'],
            'price_label' => ['required', 'string'],
        ],
        'is_active' => true,
    ]);

    $parent = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'context' => [
                'import_record_id' => 9,
            ],
        ],
    ]);

    makeRunner()->run($parent);
    $parent->refresh();

    expect($parent->status)->toBe('processed')
        ->and($parent->bulk_stats['processed'] ?? null)->toBe(2)
        ->and(TransformRecord::query()->where('parent_transform_record_id', $parent->id)->count())->toBe(0)
        ->and(TransformDummyModel::query()->where('price_label', 'BU-1')->value('title'))->toBe('Batch One')
        ->and(TransformDummyModel::query()->where('price_label', 'BU-2')->value('title'))->toBe('Batch Two');
});

test('it expands locales from configured expand definition', function (): void {
    Config::set('transform.locale_variant_resolver', FakeLocaleVariantResolver::class);

    $definition = TransformDefinition::query()->create([
        'name' => 'locale-expand',
        'destination_model' => TransformDraftMainModel::class,
        'execution_mode' => TransformExecutionMode::Expand->value,
        'expand' => [
            'locales' => [
                'source' => 'legacy.translations',
                'language_key' => 'language',
                'alias' => 'translation',
            ],
        ],
        'destination_match' => [
            'status' => 'legacy.status',
        ],
        'source_references' => [],
        'field_map' => [
            'title' => 'translation.title',
            'status' => 'legacy.status',
            'locale' => 'locale',
        ],
        'validation_rules' => [],
        'is_active' => true,
    ]);

    $parent = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'legacy' => [
                'status' => 'active',
                'translations' => [
                    ['language' => 'en', 'title' => 'English Title'],
                    ['language' => 'de', 'title' => 'German Title'],
                ],
            ],
        ],
    ]);

    makeRunner()->run($parent);
    $parent->refresh();

    expect($parent->status)->toBe('processed')
        ->and($parent->bulk_stats['total'] ?? null)->toBe(2);

    $saved = TransformDraftMainModel::query()->first();
    expect($saved)->not->toBeNull();

    expect(TransformDraftMainTranslationModel::query()->where('locale', 'en_US')->value('title'))->toBe('English Title')
        ->and(TransformDraftMainTranslationModel::query()->where('locale', 'de_DE')->value('title'))->toBe('German Title');
});

test('it filters locale expansion to configured languages only', function (): void {
    Config::set('transform.locale_variant_resolver', FakeLocaleVariantResolver::class);

    $definition = TransformDefinition::query()->create([
        'name' => 'locale-expand-only',
        'destination_model' => TransformDraftMainModel::class,
        'execution_mode' => TransformExecutionMode::Expand->value,
        'expand' => [
            'locales' => [
                'source' => 'legacy.translations',
                'language_key' => 'language',
                'alias' => 'translation',
                'only' => ['de', 'en'],
            ],
        ],
        'destination_match' => [
            'status' => 'legacy.status',
        ],
        'source_references' => [],
        'field_map' => [
            'title' => 'translation.title',
            'status' => 'legacy.status',
            'locale' => 'locale',
        ],
        'validation_rules' => [],
        'is_active' => true,
    ]);

    $parent = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'legacy' => [
                'status' => 'active',
                'translations' => [
                    ['language' => 'en', 'title' => 'English Title'],
                    ['language' => 'de', 'title' => 'German Title'],
                    ['language' => 'fr', 'title' => 'French Title'],
                ],
            ],
        ],
    ]);

    makeRunner()->run($parent);
    $parent->refresh();

    expect($parent->status)->toBe('processed')
        ->and($parent->bulk_stats['total'] ?? null)->toBe(2)
        ->and(TransformDraftMainTranslationModel::query()->count())->toBe(2)
        ->and(TransformDraftMainTranslationModel::query()->where('locale', 'fr_FR')->exists())->toBeFalse();
});

test('it can batch upsert translatable destination models in bulk mode', function (): void {
    Config::set('transform.locale_variant_resolver', FakeLocaleVariantResolver::class);

    $definition = TransformDefinition::query()->create([
        'name' => 'locale-bulk-batch',
        'destination_model' => TransformDraftBatchModel::class,
        'execution_mode' => TransformExecutionMode::Bulk->value,
        'bulk' => [
            'persist_children' => false,
            'write_strategy' => 'batch',
        ],
        'expand' => [
            'locales' => [
                'source' => 'legacy.translations',
                'language_key' => 'language',
                'alias' => 'translation',
                'only' => ['de', 'en'],
            ],
        ],
        'destination_match' => [
            'code' => 'legacy.code',
        ],
        'source_references' => [],
        'field_map' => [
            'title' => 'translation.title',
            'code' => 'legacy.code',
            'locale' => 'locale',
        ],
        'validation_rules' => [],
        'is_active' => true,
    ]);

    $parent = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'legacy' => [
                'code' => 'batch-code',
                'translations' => [
                    ['language' => 'en', 'title' => 'English Batch'],
                    ['language' => 'de', 'title' => 'German Batch'],
                ],
            ],
        ],
    ]);

    makeRunner()->run($parent);
    $parent->refresh();

    expect($parent->status)->toBe('processed')
        ->and($parent->bulk_stats['total'] ?? null)->toBe(2)
        ->and(TransformRecord::query()->where('parent_transform_record_id', $parent->id)->count())->toBe(0)
        ->and(TransformDraftBatchModel::query()->count())->toBe(1)
        ->and(TransformDraftBatchTranslationModel::query()->where('locale', 'en_US')->value('title'))->toBe('English Batch')
        ->and(TransformDraftBatchTranslationModel::query()->where('locale', 'de_DE')->value('title'))->toBe('German Batch');
});
