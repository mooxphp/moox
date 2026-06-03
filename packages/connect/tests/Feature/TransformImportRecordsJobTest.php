<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Moox\Connect\Jobs\TransformImportRecordsJob;
use Moox\Connect\Models\ApiConnection;
use Moox\Connect\Models\ApiEndpoint;
use Moox\Connect\Models\ApiImportPayloadChunk;
use Moox\Connect\Models\ApiImportRecord;
use Moox\Connect\Support\ApiImportPayloadExtractor;
use Moox\Connect\Support\TransformerRegistry;
use Moox\Connect\Tests\TestCase;

uses(TestCase::class);

test('TransformImportRecordsJob reconstructs chunked payload before mapping', function (): void {
    Schema::create('products_dummy', function (Blueprint $table): void {
        $table->id();
        $table->string('article_number')->unique();
        $table->decimal('price', 10, 2);
        $table->integer('stock')->default(0);
        $table->timestamps();
    });

    $connection = ApiConnection::create([
        'name' => 'Test',
        'base_url' => 'https://example.test',
        'api_type' => 'REST',
        'auth_type' => 'JWT',
        'login_method' => 'none',
        'auth_credentials' => null,
        'headers' => null,
        'status' => 'New',
        'notify_on_failure' => '1',
    ]);

    $endpoint = ApiEndpoint::create([
        'name' => 'Customerdata',
        'api_connection_id' => $connection->id,
        'path' => '/test',
        'method' => 'GET',
        'direct_access' => false,
        'variables' => [],
        'response_map' => [],
        'expected_response' => [],
        'field_mappings' => [
            [
                'external_field' => 'Price',
                'internal_field' => 'price',
            ],
        ],
        'transformers' => [],
        'lang_override' => null,
        'rate_limit' => null,
        'rate_window' => null,
        'status' => 'active',
        'timeout' => 30,
        'destination_model' => ProductsDummy::class,
        'key_fields' => [
            'article_number' => 'ArticleNumber',
        ],
        'external_key_field' => null,
        'list_item_path' => null,
        'list_id_key' => null,
        'parent_endpoint_id' => null,
        'route_param_key' => null,
        'variable_key' => null,
        'sync_mode' => 'append',
        'sync_scope_fields' => null,
        'purge_after_days' => null,
        'options' => null,
    ]);

    $record = ApiImportRecord::create([
        'api_connection_id' => $connection->id,
        'api_endpoint_id' => $endpoint->id,
        'external_key' => 'ext-1',
        'sync_scope_hash' => null,
        'sync_batch_id' => null,
        'payload' => [
            'chunked' => true,
        ],
        'payload_hash' => 'hash-1',
        'status' => 'new',
        'error_message' => null,
    ]);

    ApiImportPayloadChunk::create([
        'api_import_record_id' => $record->id,
        'chunk_index' => 0,
        'payload_chunk' => '{"ArticleNumber":"A-1",',
        'items_count' => null,
        'bytes_size' => null,
    ]);

    ApiImportPayloadChunk::create([
        'api_import_record_id' => $record->id,
        'chunk_index' => 1,
        'payload_chunk' => '"Price": 12.34}',
        'items_count' => null,
        'bytes_size' => null,
    ]);

    $job = new TransformImportRecordsJob($endpoint->id, batchSize: 100, syncBatchId: null);
    $job->handle(new TransformerRegistry, new ApiImportPayloadExtractor);

    $product = ProductsDummy::query()->where('article_number', 'A-1')->first();
    expect($product)->not()->toBeNull();
    expect((float) $product->price)->toBeFloat()->toEqual(12.34);

    $record->refresh();
    expect($record->status)->toBe('processed');
});

test('TransformImportRecordsJob marks record failed when key_fields resolve to null', function (): void {
    Schema::create('products_dummy', function (Blueprint $table): void {
        $table->id();
        $table->string('article_number')->unique();
        $table->decimal('price', 10, 2);
        $table->integer('stock')->default(0);
        $table->timestamps();
    });

    $connection = ApiConnection::create([
        'name' => 'Test',
        'base_url' => 'https://example.test',
        'api_type' => 'REST',
        'auth_type' => 'JWT',
        'login_method' => 'none',
        'auth_credentials' => null,
        'headers' => null,
        'status' => 'New',
        'notify_on_failure' => '1',
    ]);

    $endpoint = ApiEndpoint::create([
        'name' => 'Customerdata',
        'api_connection_id' => $connection->id,
        'path' => '/test',
        'method' => 'GET',
        'direct_access' => false,
        'variables' => [],
        'response_map' => [],
        'expected_response' => [],
        'field_mappings' => [
            [
                'external_field' => 'Price',
                'internal_field' => 'price',
            ],
        ],
        'transformers' => [],
        'lang_override' => null,
        'rate_limit' => null,
        'rate_window' => null,
        'status' => 'active',
        'timeout' => 30,
        'destination_model' => ProductsDummy::class,
        'key_fields' => [
            'article_number' => 'ArticleNumber',
        ],
        'external_key_field' => null,
        'list_item_path' => null,
        'list_id_key' => null,
        'parent_endpoint_id' => null,
        'route_param_key' => null,
        'variable_key' => null,
        'sync_mode' => 'append',
        'sync_scope_fields' => null,
        'purge_after_days' => null,
        'options' => null,
    ]);

    $record = ApiImportRecord::create([
        'api_connection_id' => $connection->id,
        'api_endpoint_id' => $endpoint->id,
        'external_key' => 'ext-2',
        'sync_scope_hash' => null,
        'sync_batch_id' => null,
        'payload' => [
            'chunked' => true,
        ],
        'payload_hash' => 'hash-2',
        'status' => 'new',
        'error_message' => null,
    ]);

    ApiImportPayloadChunk::create([
        'api_import_record_id' => $record->id,
        'chunk_index' => 0,
        'payload_chunk' => '{"Price": 9.99}',
        'items_count' => null,
        'bytes_size' => null,
    ]);

    $job = new TransformImportRecordsJob($endpoint->id, batchSize: 100, syncBatchId: null);
    $job->handle(new TransformerRegistry, new ApiImportPayloadExtractor);

    $record->refresh();
    expect($record->status)->toBe('failed');

    $product = ProductsDummy::query()->where('article_number', '')->first();
    expect($product)->toBeNull();
});

final class ProductsDummy extends Model
{
    protected $table = 'products_dummy';

    protected $fillable = [
        'article_number',
        'price',
        'stock',
    ];
}
