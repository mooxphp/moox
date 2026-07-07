<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;
use Tests\TestCase;

require_once dirname(__DIR__).'/Support/TransformTestSupport.php';

uses(TestCase::class);

test('it transforms from multiple source rows and tables into one destination model', function (): void {
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

    (makeRunner())->run($record);

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

test('it fails validation and stores validation errors', function (): void {
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
    expect($record->error_message)->toBe('Validation failed.');
});

test('it rejects definition when destination model does not exist', function (): void {
    createTestTables();
    createDefinition([
        'destination_model' => 'App\\Models\\DefinitelyMissingModel',
        'destination_match' => [
            'title' => 'legacy.title',
        ],
        'field_map' => [
            'title' => 'legacy.title',
        ],
    ]);
})->throws(ValidationException::class);

test('it validates based on model metadata when no extra rules are provided', function (): void {
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
    expect($record->validation_status)->toBe('invalid');
    expect($record->validation_errors)->toHaveKey('stock');
});

test('it allows adding extra validation rules on top of model-based validation', function (): void {
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
    expect($record->validation_status)->toBe('invalid');
    expect($record->validation_errors)->toHaveKey('title');
});

test('it rejects definition with non existing file reference', function (): void {
    createTestTables();

    TransformDefinition::query()->create([
        'name' => 'Invalid file definition',
        'destination_model' => TransformDummyModel::class,
        'destination_match' => [
            'title' => 'file.title',
        ],
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

test('it rejects record when no source is provided by record or definition', function (): void {
    createTestTables();
    $definition = createDefinition([
        'source_references' => [],
    ]);

    TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
    ]);
})->throws(ValidationException::class);

test('it writes translated fields for draft-like destination model', function (): void {
    createTestTables();
    $definition = createDefinition([
        'destination_model' => TransformDraftMainModel::class,
        'field_map' => [
            'title' => 'legacy.title',
            'status' => 'legacy.status',
        ],
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'legacy' => [
                'title' => 'Translated Title',
                'status' => 'active',
            ],
        ],
    ]);

    makeRunner()->run($record);
    $record->refresh();

    expect($record->status)->toBe('processed');
    expect($record->validation_status)->toBe('valid');

    $saved = TransformDraftMainModel::query()->first();
    expect($saved)->not()->toBeNull();
    expect($saved?->status)->toBe('draft');
    $locale = (string) config('transform.default_locale', app()->getLocale());
    $translation = TransformDraftMainTranslationModel::query()
        ->where('transform_draft_main_model_id', $saved?->id)
        ->where('locale', $locale)
        ->first();
    expect($translation)->not()->toBeNull();
    expect($translation?->title)->toBe('Translated Title');
});
