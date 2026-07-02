<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Moox\Transform\Models\TransformRecord;
use Tests\TestCase;

require_once dirname(__DIR__, 1).'/Support/TransformTestSupport.php';

uses(TestCase::class);

test('it applies inline map operations via the operation registry', function (): void {
    createTestTables();

    Schema::create('legacy_products', function (Blueprint $table): void {
        $table->id();
        $table->string('sku')->unique();
        $table->integer('value');
    });

    DB::table('legacy_products')->insert([
        'id' => 1,
        'sku' => 'SKU-1',
        'value' => 1,
    ]);

    $definition = createDefinition([
        'destination_model' => TransformDummyModel::class,
        'destination_match' => [
            'title' => 'product.sku',
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
            'title' => 'product.sku',
            'price_label' => 'product.value|map:1=LOW,2=HIGH,*=UNKNOWN',
        ],
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
    ]);

    makeRunner()->run($record);
    $record->refresh();

    expect($record->status)->toBe('processed');
    expect(TransformDummyModel::query()->first()?->price_label)->toBe('LOW');
});
