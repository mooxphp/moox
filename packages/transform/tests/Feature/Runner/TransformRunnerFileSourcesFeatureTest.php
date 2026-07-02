<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;
use Tests\TestCase;

require_once dirname(__DIR__, 2).'/Support/TransformTestSupport.php';

uses(TestCase::class);

test('it transforms data from a json source file', function (): void {
    createTestTables();

    $jsonPath = storage_path('framework/testing/transform-source.json');
    File::ensureDirectoryExists(dirname($jsonPath));
    File::put($jsonPath, json_encode([
        'legacy' => [
            'title' => 'JSON Product',
            'inventory' => 21,
        ],
    ], JSON_THROW_ON_ERROR));

    try {
        $definition = createDefinition([
            'source_references' => [
                [
                    'source_type' => 'file_json',
                    'path' => $jsonPath,
                ],
            ],
            'field_map' => [
                'title' => 'legacy.title',
                'stock' => 'legacy.inventory',
            ],
        ]);

        $record = TransformRecord::query()->create([
            'transform_definition_id' => $definition->id,
        ]);

        makeRunner()->run($record);
        $record->refresh();

        expect($record->status)->toBe('processed');
        expect($record->validation_status)->toBe('valid');
    } finally {
        File::delete($jsonPath);
    }
});

test('it rejects definition when json file is invalid', function (): void {
    createTestTables();

    $jsonPath = storage_path('framework/testing/transform-invalid.json');
    File::ensureDirectoryExists(dirname($jsonPath));
    File::put($jsonPath, '{invalid-json');

    try {
        TransformDefinition::query()->create([
            'name' => 'Invalid json definition',
            'destination_model' => TransformDummyModel::class,
            'destination_match' => [
                'title' => 'legacy.title',
            ],
            'source_references' => [
                [
                    'source_type' => 'file_json',
                    'path' => $jsonPath,
                ],
            ],
            'field_map' => [
                'title' => 'legacy.title',
            ],
            'validation_rules' => [],
            'is_active' => true,
        ]);
    } finally {
        File::delete($jsonPath);
    }
})->throws(ValidationException::class);

test('it transforms data from a csv source file', function (): void {
    createTestTables();

    $csvPath = storage_path('framework/testing/transform-source.csv');
    File::ensureDirectoryExists(dirname($csvPath));
    File::put($csvPath, "sku,title,inventory\nP-42,CSV Product,17\n");

    try {
        $definition = createDefinition([
            'destination_match' => [
                'title' => 'title',
            ],
            'source_references' => [
                [
                    'source_type' => 'file_csv',
                    'path' => $csvPath,
                    'key_column' => 'sku',
                    'row_key' => 'P-42',
                ],
            ],
            'field_map' => [
                'title' => 'title',
                'stock' => 'inventory',
            ],
        ]);

        $record = TransformRecord::query()->create([
            'transform_definition_id' => $definition->id,
        ]);

        makeRunner()->run($record);
        $record->refresh();

        expect($record->status)->toBe('processed');
        expect($record->validation_status)->toBe('valid');
    } finally {
        File::delete($csvPath);
    }
});

test('it rejects csv definition when key column is missing in header', function (): void {
    createTestTables();

    $csvPath = storage_path('framework/testing/transform-invalid.csv');
    File::ensureDirectoryExists(dirname($csvPath));
    File::put($csvPath, "id,title\n1,CSV Product\n");

    try {
        TransformDefinition::query()->create([
            'name' => 'Invalid csv definition',
            'destination_model' => TransformDummyModel::class,
            'destination_match' => [
                'title' => 'title',
            ],
            'source_references' => [
                [
                    'source_type' => 'file_csv',
                    'path' => $csvPath,
                    'key_column' => 'sku',
                    'row_key' => 'P-42',
                ],
            ],
            'field_map' => [
                'title' => 'title',
            ],
            'validation_rules' => [],
            'is_active' => true,
        ]);
    } finally {
        File::delete($csvPath);
    }
})->throws(ValidationException::class);
