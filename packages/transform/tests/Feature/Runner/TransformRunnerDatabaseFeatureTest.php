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
        'destination_match' => [
            'price_label' => 'product.sku',
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
        'destination_match' => [
            'stock' => 'legacy.inventory',
        ],
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
    expect($record->error_message)->toStartWith('Validation failed.');
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
        'destination_match' => [
            'stock' => 'legacy.inventory',
        ],
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
        'destination_match' => [
            'title' => 'title',
        ],
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

    expect($record->status)->toBe('updated');
    expect($record->validation_status)->toBe('valid');
});

test('it fails when destination_match cannot be fully resolved', function (): void {
    createTestTables();

    $definition = createDefinition([
        'destination_model' => TransformDummyModel::class,
        'destination_match' => [
            'title' => 'legacy.title',
        ],
        'field_map' => [
            'title' => 'legacy.title',
            'stock' => 'legacy.inventory',
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

    expect($record->status)->toBe('failed');
    expect($record->error_message)->toContain('destination_match could not be fully resolved');
    expect($record->validation_errors)->toHaveKey('destination_conflict');
    expect($record->validation_errors['destination_conflict']['type'])->toBe('incomplete_destination_match');
    expect($record->validation_errors['destination_conflict']['missing_fields'])->toContain('title (from legacy.title)');
});

test('it fails with structured conflict when multiple destination records match', function (): void {
    createTestTables();

    $first = TransformDummyModel::query()->create([
        'title' => 'Shared Title',
        'stock' => 1,
        'price_label' => 'A',
    ]);
    $second = TransformDummyModel::query()->create([
        'title' => 'Shared Title',
        'stock' => 2,
        'price_label' => 'B',
    ]);

    $definition = createDefinition([
        'name' => 'duplicate-match-definition',
        'destination_model' => TransformDummyModel::class,
        'destination_match' => [
            'title' => 'legacy.title',
        ],
        'field_map' => [
            'title' => 'legacy.title',
            'stock' => 'legacy.inventory',
        ],
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'legacy' => [
                'title' => 'Shared Title',
                'inventory' => 9,
            ],
        ],
    ]);

    makeRunner()->run($record);
    $record->refresh();

    expect($record->status)->toBe('failed');
    expect($record->validation_errors['destination_conflict']['type'])->toBe('multiple_destination_matches');
    expect($record->validation_errors['destination_conflict']['existing_destination_keys'])
        ->toEqualCanonicalizing([(string) $first->getKey(), (string) $second->getKey()]);
    expect($record->validation_errors['destination_conflict']['source']['primary_source_id'])->toBe('Shared Title');
    expect($record->validation_errors['destination_conflict']['source']['references'][0]['source_path'])->toBe('legacy.title');
    expect($record->error_message)->toContain((string) $first->getKey())
        ->and($record->error_message)->toContain((string) $second->getKey());
});

test('it fails with structured conflict on unique constraint violations', function (): void {
    createTestTables();

    $existing = TransformSoftDeleteDummyModel::query()->create([
        'title' => 'Original',
        'external_reference' => 'LEG-1',
    ]);

    $definition = createDefinition([
        'name' => 'unique-violation-definition',
        'destination_model' => TransformSoftDeleteDummyModel::class,
        'destination_match' => [
            'title' => 'legacy.title',
        ],
        'field_map' => [
            'title' => 'legacy.title',
            'external_reference' => 'legacy.ref',
        ],
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'legacy' => [
                'ref' => 'LEG-1',
                'title' => 'Different Title',
            ],
        ],
    ]);

    makeRunner()->run($record);
    $record->refresh();

    expect($record->status)->toBe('failed');
    expect($record->validation_errors['destination_conflict']['type'])->toBe('unique_constraint_violation');
    expect($record->validation_errors['destination_conflict']['source']['references'][0]['source_path'])->toBe('legacy.title');
    expect($record->validation_errors['destination_conflict']['source']['primary_source_id'])->toBe('Different Title');
    expect($record->validation_errors['destination_conflict']['destination_match']['title'])->toBe('Different Title');
    expect($record->validation_errors['destination_conflict']['existing_destination_keys'])->toBe([(string) $existing->getKey()]);
    expect($record->error_message)->toContain((string) $existing->getKey());
});

test('it includes db source identity in conflict output', function (): void {
    createTestTables();

    Schema::create('legacy_products', function (Blueprint $table): void {
        $table->id();
        $table->string('sku')->unique();
        $table->string('title');
        $table->integer('inventory');
    });

    DB::table('legacy_products')->insert([
        'id' => 99,
        'sku' => 'SKU-99',
        'title' => 'Shared',
        'inventory' => 1,
    ]);
    TransformDummyModel::query()->create([
        'title' => 'Shared',
        'stock' => 1,
        'price_label' => 'X',
    ]);
    TransformDummyModel::query()->create([
        'title' => 'Shared',
        'stock' => 2,
        'price_label' => 'Y',
    ]);

    $definition = createDefinition([
        'destination_model' => TransformDummyModel::class,
        'destination_match' => [
            'title' => 'product.title',
        ],
        'source_references' => [
            [
                'source_type' => 'db_table',
                'table' => 'legacy_products',
                'row_key' => 99,
                'key_column' => 'id',
                'alias' => 'product',
            ],
        ],
        'field_map' => [
            'title' => 'product.title',
            'stock' => 'product.inventory',
        ],
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
    ]);

    makeRunner()->run($record);
    $record->refresh();

    expect($record->status)->toBe('failed');
    expect($record->validation_errors['destination_conflict']['source']['references'][0])
        ->toMatchArray([
            'source_type' => 'db_table',
            'table' => 'legacy_products',
            'key_column' => 'id',
            'source_id' => 99,
        ]);
    expect($record->error_message)->toContain('legacy_products')
        ->and($record->error_message)->toContain('99');
});

test('it normalizes integer source values when matching string destination columns', function (): void {
    createTestTables();

    TransformDummyModel::query()->create([
        'title' => 'Existing',
        'stock' => 1,
        'price_label' => '42',
    ]);

    Schema::create('legacy_products', function (Blueprint $table): void {
        $table->id();
        $table->string('title');
        $table->integer('inventory');
    });

    DB::table('legacy_products')->insert([
        'id' => 42,
        'title' => 'Updated',
        'inventory' => 9,
    ]);

    $definition = createDefinition([
        'destination_model' => TransformDummyModel::class,
        'destination_match' => [
            'price_label' => 'product.id',
        ],
        'source_references' => [
            [
                'source_type' => 'db_table',
                'table' => 'legacy_products',
                'row_key' => 42,
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

    expect($record->status)->toBe('updated');
    expect(TransformDummyModel::query()->count())->toBe(1);

    $saved = TransformDummyModel::query()->first();
    expect($saved?->title)->toBe('Updated');
    expect($saved?->price_label)->toBe('42');
});

test('it restores and updates soft deleted destination models matched by destination_match', function (): void {
    createTestTables();

    $existing = TransformSoftDeleteDummyModel::query()->create([
        'title' => 'Old Title',
        'external_reference' => 'LEG-1',
    ]);
    $existing->delete();

    $definition = createDefinition([
        'destination_model' => TransformSoftDeleteDummyModel::class,
        'destination_match' => [
            'external_reference' => 'legacy.ref',
        ],
        'field_map' => [
            'title' => 'legacy.title',
            'external_reference' => 'legacy.ref',
        ],
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'legacy' => [
                'ref' => 'LEG-1',
                'title' => 'Restored Title',
            ],
        ],
    ]);

    makeRunner()->run($record);
    $record->refresh();

    expect($record->status)->toBe('updated');
    expect(TransformSoftDeleteDummyModel::query()->count())->toBe(1);
    expect(TransformSoftDeleteDummyModel::withTrashed()->count())->toBe(1);

    $saved = TransformSoftDeleteDummyModel::query()->first();
    expect($saved?->getKey())->toBe($existing->getKey());
    expect($saved?->title)->toBe('Restored Title');
    expect($saved?->external_reference)->toBe('LEG-1');
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
        'destination_match' => [
            'code' => 'meta_src.code',
        ],
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

test('it can iterate db_table bulk sources in cursor chunks without child records', function (): void {
    createTestTables();

    Schema::create('legacy_products', function (Blueprint $table): void {
        $table->id();
        $table->string('title');
        $table->integer('inventory');
    });

    DB::table('legacy_products')->insert([
        ['id' => 1, 'title' => 'Switch', 'inventory' => 19],
        ['id' => 2, 'title' => 'Adapter', 'inventory' => 7],
        ['id' => 3, 'title' => 'Cable', 'inventory' => 5],
    ]);

    $definition = createDefinition([
        'name' => 'cursor-bulk-definition',
        'destination_model' => TransformDummyModel::class,
        'execution_mode' => 'bulk',
        'bulk' => [
            'persist_children' => false,
            'write_strategy' => 'batch',
            'source' => [
                'strategy' => 'cursor',
                'chunk_size' => 2,
            ],
        ],
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

    expect($record->status)->toBe('processed')
        ->and($record->bulk_stats['total'] ?? null)->toBe(3)
        ->and($record->bulk_stats['processed'] ?? null)->toBe(3)
        ->and(TransformRecord::query()->where('parent_transform_record_id', $record->id)->count())->toBe(0)
        ->and(TransformDummyModel::query()->count())->toBe(3)
        ->and(TransformDummyModel::query()->where('price_label', '3')->value('title'))->toBe('Cable');
});

test('it does not overwrite existing destination attributes with null when graceful degradation is enabled', function (): void {
    createTestTables();

    TransformDummyModel::query()->create([
        'title' => 'Existing product',
        'stock' => 42,
        'price_label' => 'SKU-1',
    ]);

    $definition = createDefinition([
        'destination_model' => TransformDummyModel::class,
        'destination_match' => [
            'price_label' => 'legacy.sku',
        ],
        'source_references' => [],
        'field_map' => [
            'price_label' => 'legacy.sku',
            'stock' => 'legacy.inventory',
        ],
        'validation_rules' => [
            'stock' => ['nullable', 'integer'],
        ],
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'legacy' => [
                'sku' => 'SKU-1',
                'inventory' => null,
            ],
        ],
    ]);

    makeRunner()->run($record);
    $record->refresh();

    expect($record->status)->toBe('updated')
        ->and(TransformDummyModel::query()->where('price_label', 'SKU-1')->value('stock'))->toBe(42);
});
