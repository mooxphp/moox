<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;
use Tests\TestCase;

require_once dirname(__DIR__, 2).'/Support/TransformTestSupport.php';

uses(TestCase::class);

test('it rejects definition when destination model does not exist', function (): void {
    createTestTables();

    createDefinition([
        'destination_model' => 'App\\Models\\DefinitelyMissingModel',
        'field_map' => [
            'title' => 'legacy.title',
        ],
    ]);
})->throws(ValidationException::class);

test('it rejects definition with non existing file reference', function (): void {
    createTestTables();

    TransformDefinition::query()->create([
        'name' => 'Invalid file definition',
        'destination_model' => TransformDummyModel::class,
        'source_references' => [
            [
                'source_type' => 'file_json',
                'path' => '/definitely/not/here.json',
                'alias' => 'file',
            ],
        ],
        'field_map' => [
            'title' => 'file.title',
        ],
        'validation_rules' => [],
        'is_active' => true,
    ]);
})->throws(ValidationException::class);

test('it rejects definition with unknown db connection', function (): void {
    createTestTables();

    TransformDefinition::query()->create([
        'name' => 'Invalid db connection definition',
        'destination_model' => TransformDummyModel::class,
        'source_references' => [
            [
                'source_type' => 'db_table',
                'connection' => 'not_existing_connection',
                'table' => 'transform_dummy_models',
                'key_column' => 'id',
                'row_key' => 1,
            ],
        ],
        'field_map' => [
            'title' => 'title',
        ],
        'validation_rules' => [],
        'is_active' => true,
    ]);
})->throws(ValidationException::class);

test('it allows db_table source without row_key for iteration', function (): void {
    createTestTables();

    $definition = TransformDefinition::query()->create([
        'name' => 'Invalid db row key definition',
        'destination_model' => TransformDummyModel::class,
        'source_references' => [
            [
                'source_type' => 'db_table',
                'connection' => (string) config('database.default'),
                'table' => 'transform_dummy_models',
                'key_column' => 'id',
            ],
        ],
        'field_map' => [
            'title' => 'title',
        ],
        'validation_rules' => [],
        'is_active' => true,
    ]);

    expect($definition->exists)->toBeTrue();
});

test('it rejects db_table source with schema qualified table name', function (): void {
    createTestTables();

    TransformDefinition::query()->create([
        'name' => 'Invalid schema table definition',
        'destination_model' => TransformDummyModel::class,
        'source_references' => [
            [
                'source_type' => 'db_table',
                'connection' => (string) config('database.default'),
                'table' => 'otherdb.transform_dummy_models',
                'key_column' => 'id',
                'row_key' => 1,
            ],
        ],
        'field_map' => [
            'title' => 'title',
        ],
        'validation_rules' => [],
        'is_active' => true,
    ]);
})->throws(ValidationException::class);

test('it rejects record when no source is provided by record or definition', function (): void {
    createTestTables();
    $definition = createDefinition([
        'source_references' => [],
    ]);

    TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
    ]);
})->throws(ValidationException::class);

test('it rejects destination_match fields not present in field_map', function (): void {
    createTestTables();
    $jsonPath = storage_path('framework/testing/transform-destination-match.json');
    File::ensureDirectoryExists(dirname($jsonPath));
    File::put($jsonPath, json_encode(['sku' => 'A-1', 'title' => 'Example']));

    TransformDefinition::query()->create([
        'name' => 'Invalid destination match definition',
        'destination_model' => TransformDummyModel::class,
        'destination_match' => [
            'price_label' => 'source.sku',
        ],
        'source_references' => [
            [
                'source_type' => 'file_json',
                'path' => $jsonPath,
                'alias' => 'source',
            ],
        ],
        'field_map' => [
            'title' => 'source.title',
        ],
        'validation_rules' => [],
        'is_active' => true,
    ]);
})->throws(ValidationException::class);
