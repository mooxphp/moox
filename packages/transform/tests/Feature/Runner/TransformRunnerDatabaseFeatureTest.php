<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Moox\Transform\Models\TransformRecord;
use Tests\TestCase;

require_once dirname(__DIR__, 2).'/Support/TransformTestSupport.php';

uses(TestCase::class);

test('it transforms from multiple db sources into one destination model', function (): void {
    createTestTables();
    Schema::create('legacy_products', function (Blueprint $table): void {
        $table->id();
        $table->string('sku')->unique();
        $table->string('title');
        $table->integer('inventory');
    });
    Schema::create('legacy_prices', function (Blueprint $table): void {
        $table->id();
        $table->string('sku')->unique();
        $table->string('label');
    });

    DB::table('legacy_products')->insert([
        'sku' => 'P-15',
        'title' => 'Switch',
        'inventory' => 19,
    ]);
    DB::table('legacy_prices')->insert([
        'sku' => 'P-15',
        'label' => '9.99 EUR',
    ]);

    $definition = createDefinition([
        'destination_model' => TransformDummyModel::class,
        'source_references' => [
            [
                'source_type' => 'db_table',
                'table' => 'legacy_products',
                'row_key' => 'P-15',
                'key_column' => 'sku',
                'alias' => 'product',
            ],
            [
                'source_type' => 'db_table',
                'table' => 'legacy_prices',
                'row_key' => 'P-15',
                'key_column' => 'sku',
                'alias' => 'price',
            ],
        ],
        'field_map' => [
            'title' => 'product.title',
            'stock' => 'product.inventory',
            'price_label' => 'price.label',
        ],
        'validation_rules' => [
            'title' => ['required', 'string'],
            'stock' => ['required', 'integer'],
            'price_label' => ['required', 'string'],
        ],
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
    ]);

    makeRunner()->run($record);
    $record->refresh();

    expect($record->status)->toBe('processed');
    expect($record->validation_status)->toBe('valid');
    expect($record->degraded)->toBeFalse();

    $saved = TransformDummyModel::query()->first();
    expect($saved)->not()->toBeNull();
    expect($saved?->title)->toBe('Switch');
    expect($saved?->stock)->toBe(19);
    expect($saved?->price_label)->toBe('9.99 EUR');
});

test('it updates an existing destination model using destination_match', function (): void {
    createTestTables();
    Schema::create('legacy_products', function (Blueprint $table): void {
        $table->id();
        $table->string('sku')->unique();
        $table->string('title');
        $table->integer('inventory');
    });

    DB::table('legacy_products')->insert([
        'id' => 1,
        'sku' => 'SKU-1',
        'title' => 'Old Name',
        'inventory' => 2,
    ]);

    $definition = createDefinition([
        'destination_model' => TransformDummyModel::class,
        'destination_match' => [
            'price_label' => 'product.sku',
        ],
        'source_references' => [
            [
                'source_type' => 'db_table',
                'table' => 'legacy_products',
                'row_key' => 1,
                'key_column' => 'id',
                'alias' => 'product',
            ],
        ],
        'field_map' => [
            'title' => 'product.title',
            'stock' => 'product.inventory',
            'price_label' => 'product.sku',
        ],
    ]);

    $firstRecord = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
    ]);
    makeRunner()->run($firstRecord);

    DB::table('legacy_products')->where('id', 1)->update([
        'title' => 'New Name',
        'inventory' => 9,
    ]);

    $secondRecord = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
    ]);
    makeRunner()->run($secondRecord);
    $secondRecord->refresh();

    expect($secondRecord->status)->toBe('updated');
    expect(TransformDummyModel::query()->count())->toBe(1);

    $saved = TransformDummyModel::query()->first();
    expect($saved?->title)->toBe('New Name');
    expect($saved?->stock)->toBe(9);
    expect($saved?->price_label)->toBe('SKU-1');
});

test('it skips processing when destination match and input hash are unchanged', function (): void {
    createTestTables();
    Schema::create('legacy_products', function (Blueprint $table): void {
        $table->id();
        $table->string('sku')->unique();
        $table->string('title');
        $table->integer('inventory');
    });

    DB::table('legacy_products')->insert([
        'id' => 1,
        'sku' => 'SKU-1',
        'title' => 'Stable Name',
        'inventory' => 5,
    ]);

    $definition = createDefinition([
        'destination_model' => TransformDummyModel::class,
        'destination_match' => [
            'price_label' => 'product.sku',
        ],
        'source_references' => [
            [
                'source_type' => 'db_table',
                'table' => 'legacy_products',
                'row_key' => 1,
                'key_column' => 'id',
                'alias' => 'product',
            ],
        ],
        'field_map' => [
            'title' => 'product.title',
            'stock' => 'product.inventory',
            'price_label' => 'product.sku',
        ],
    ]);

    $firstRecord = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
    ]);
    makeRunner()->run($firstRecord);

    $secondRecord = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
    ]);
    makeRunner()->run($secondRecord);
    $secondRecord->refresh();

    expect($secondRecord->status)->toBe('skipped');
    expect($secondRecord->validation_status)->toBe('valid');
    expect(TransformDummyModel::query()->count())->toBe(1);
});

test('it fails validation and stores errors', function (): void {
    createTestTables();
    $definition = createDefinition([
        'destination_model' => TransformDummyModel::class,
        'field_map' => [
            'title' => 'legacy.title',
            'stock' => 'legacy.inventory',
        ],
        'validation_rules' => [
            'title' => ['required', 'string'],
            'stock' => ['required', 'integer'],
        ],
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'legacy' => [
                'inventory' => 5,
            ],
        ],
    ]);

    makeRunner()->run($record);
    $record->refresh();

    expect($record->status)->toBe('failed_validation');
    expect($record->validation_status)->toBe('invalid');
    expect($record->validation_errors)->toHaveKey('title');
    expect($record->error_message)->toBe('Validation failed.');
});

test('it validates with model metadata without extra rules', function (): void {
    createTestTables();
    $definition = createDefinition([
        'destination_model' => TransformDummyModel::class,
        'field_map' => [
            'title' => 'legacy.title',
            'stock' => 'legacy.inventory',
        ],
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'legacy' => [
                'title' => 'Adapter',
                'inventory' => 'not-an-integer',
            ],
        ],
    ]);

    makeRunner()->run($record);
    $record->refresh();

    expect($record->status)->toBe('failed_validation');
    expect($record->validation_errors)->toHaveKey('stock');
});

test('it merges custom extra rules on top of model validation', function (): void {
    createTestTables();
    $definition = createDefinition([
        'destination_model' => TransformDummyModel::class,
        'field_map' => [
            'title' => 'legacy.title',
            'stock' => 'legacy.inventory',
        ],
        'validation_rules' => [
            'title' => ['required', 'string', 'min:3'],
        ],
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'legacy' => [
                'title' => 'A',
                'inventory' => 12,
            ],
        ],
    ]);

    makeRunner()->run($record);
    $record->refresh();

    expect($record->status)->toBe('failed_validation');
    expect($record->validation_errors)->toHaveKey('title');
});

test('it uses db_default connection alias for db_table source', function (): void {
    createTestTables();

    DB::table('transform_dummy_models')->insert([
        'id' => 7,
        'title' => 'From Default Connection',
        'stock' => 11,
        'price_label' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $definition = createDefinition([
        'destination_model' => TransformDummyModel::class,
        'source_references' => [
            [
                'source_type' => 'db_table',
                'connection' => 'db_default',
                'table' => 'transform_dummy_models',
                'key_column' => 'id',
                'row_key' => 7,
                'columns' => ['title', 'stock'],
            ],
        ],
        'field_map' => [
            'title' => 'title',
            'stock' => 'stock',
        ],
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_references' => $definition->source_references,
    ]);

    makeRunner()->run($record);
    $record->refresh();

    expect($record->status)->toBe('processed');
    expect($record->validation_status)->toBe('valid');
});

test('it decodes json strings for destination array casts', function (): void {
    createTestTables();

    Schema::create('legacy_json_meta', function (Blueprint $table): void {
        $table->id();
        $table->string('code')->unique();
        $table->json('meta');
    });

    DB::table('legacy_json_meta')->insert([
        'code' => 'X-1',
        'meta' => json_encode([
            'width' => 12.5,
            'height' => 8.0,
        ], JSON_THROW_ON_ERROR),
    ]);

    $definition = createDefinition([
        'destination_model' => TransformJsonDummyModel::class,
        'source_references' => [
            [
                'source_type' => 'db_table',
                'connection' => 'db_default',
                'table' => 'legacy_json_meta',
                'key_column' => 'code',
                'row_key' => 'X-1',
                'columns' => ['code', 'meta'],
                'alias' => 'meta_src',
            ],
        ],
        'field_map' => [
            'code' => 'meta_src.code',
            'meta' => 'meta_src.meta',
        ],
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
    ]);

    makeRunner()->run($record);
    $record->refresh();

    expect($record->status)->toBe('processed');
    expect($record->validation_status)->toBe('valid');

    $saved = TransformJsonDummyModel::query()->first();
    expect($saved?->meta)->toMatchArray([
        'width' => 12.5,
        'height' => 8.0,
    ]);
});

test('it iterates db_table source when row_key is omitted', function (): void {
    createTestTables();

    Schema::create('legacy_products', function (Blueprint $table): void {
        $table->id();
        $table->string('title');
        $table->integer('inventory');
    });

    DB::table('legacy_products')->insert([
        ['id' => 1, 'title' => 'Switch', 'inventory' => 19],
        ['id' => 2, 'title' => 'Adapter', 'inventory' => 7],
    ]);

    $definition = createDefinition([
        'destination_model' => TransformDummyModel::class,
        'destination_match' => [
            'price_label' => 'product.id',
        ],
        'source_references' => [
            [
                'source_type' => 'db_table',
                'connection' => 'db_default',
                'table' => 'legacy_products',
                'key_column' => 'id',
                'alias' => 'product',
            ],
        ],
        'field_map' => [
            'title' => 'product.title',
            'stock' => 'product.inventory',
            'price_label' => 'product.id',
        ],
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
    ]);

    makeRunner()->run($record);
    $record->refresh();

    expect($record->status)->toBe('processed');
    expect($record->validation_status)->toBe('valid');
    expect(TransformDummyModel::query()->count())->toBe(2);

    $first = TransformDummyModel::query()->where('price_label', '1')->first();
    $second = TransformDummyModel::query()->where('price_label', '2')->first();

    expect($first?->title)->toBe('Switch');
    expect($first?->stock)->toBe(19);
    expect($second?->title)->toBe('Adapter');
    expect($second?->stock)->toBe(7);
});
