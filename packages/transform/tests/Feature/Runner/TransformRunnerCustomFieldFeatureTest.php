<?php

declare(strict_types=1);

use Moox\Transform\Models\TransformRecord;
use Moox\Transform\Support\Execution\CustomFieldTransformSupport;
use Tests\TestCase;

require_once dirname(__DIR__, 2).'/Support/TransformTestSupport.php';

uses(TestCase::class);

test('it partitions builder custom fields from model attributes', function (): void {
    $model = new TransformCustomFieldDummyModel;

    $partitioned = CustomFieldTransformSupport::partitionResolvedData($model, [
        'title' => 'Demo',
        'material' => 'Stahl',
        'dimension' => '10x20',
        '_builder_values_locale' => 'de_DE',
    ]);

    expect($partitioned['model_data'])->toBe(['title' => 'Demo'])
        ->and($partitioned['custom_fields'])->toBe([
            'material' => 'Stahl',
            'dimension' => '10x20',
        ])
        ->and($partitioned['custom_fields_locale'])->toBe('de_DE');
});

test('it persists custom fields after the destination model is saved', function (): void {
    createTestTables();
    TransformCustomFieldDummyModel::$lastPersistedCustomFields = [];

    $definition = createDefinition([
        'destination_model' => TransformCustomFieldDummyModel::class,
        'destination_match' => [
            'title' => 'legacy.title',
        ],
        'field_map' => [
            'title' => 'legacy.title',
            'material' => 'legacy.material',
            'is-available' => 'legacy.is_available',
        ],
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'legacy' => [
                'title' => 'SKU-1',
                'material' => 'Alu',
                'is_available' => true,
            ],
        ],
    ]);

    makeRunner()->run($record);
    $record->refresh();

    $saved = TransformCustomFieldDummyModel::query()->first();

    expect($record->status)->toBe('processed')
        ->and($saved)->not->toBeNull()
        ->and($saved?->title)->toBe('SKU-1')
        ->and(TransformCustomFieldDummyModel::$lastPersistedCustomFields)->toBe([
            'material' => 'Alu',
            'is-available' => true,
        ]);
});

test('it skips empty custom field values so existing values are merged not wiped', function (): void {
    createTestTables();
    TransformCustomFieldDummyModel::$lastPersistedCustomFields = [];

    $definition = createDefinition([
        'destination_model' => TransformCustomFieldDummyModel::class,
        'destination_match' => [
            'title' => 'legacy.title',
        ],
        'field_map' => [
            'title' => 'legacy.title',
            'material' => 'legacy.material',
            'dimension' => 'legacy.dimension',
        ],
    ]);

    TransformCustomFieldDummyModel::query()->create(['title' => 'SKU-1']);
    TransformCustomFieldDummyModel::$lastPersistedCustomFields = ['material' => 'Stahl'];

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'legacy' => [
                'title' => 'SKU-1',
                'material' => null,
                'dimension' => '10x20',
            ],
        ],
    ]);

    makeRunner()->run($record);

    expect(TransformCustomFieldDummyModel::$lastPersistedCustomFields)->toBe([
        'dimension' => '10x20',
    ]);
});
