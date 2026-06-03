<?php

declare(strict_types=1);

namespace Moox\Connect\Tests\Unit\Support;

use Moox\Connect\Models\ApiConnection;
use Moox\Connect\Models\ApiEndpoint;
use Moox\Connect\Models\ApiImportPayloadChunk;
use Moox\Connect\Models\ApiImportRecord;
use Moox\Connect\Support\ApiImportPayloadExtractor;
use Moox\Connect\Tests\TestCase;

class ApiImportPayloadExtractorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    protected function tearDown(): void
    {
        $this->artisan('db:wipe');
        $this->artisan('optimize:clear');
        parent::tearDown();
    }

    public function test_it_returns_payload_as_is_when_not_chunked(): void
    {
        $connection = ApiConnection::create([
            'name' => 'Test',
            'base_url' => 'https://example.test',
            'api_type' => 'REST',
            'auth_type' => 'JWT',
            'auth_credentials' => null,
            'headers' => null,
            'status' => 'New',
        ]);

        $endpoint = ApiEndpoint::create([
            'name' => 'Test Endpoint',
            'api_connection_id' => $connection->id,
            'path' => '/test',
            'method' => 'GET',
            'direct_access' => false,
            'variables' => [],
            'response_map' => [],
            'expected_response' => [],
            'field_mappings' => [],
            'transformers' => [],
            'lang_override' => null,
            'rate_limit' => null,
            'rate_window' => null,
            'status' => 'new',
            'timeout' => 30,
            'destination_model' => null,
            'key_fields' => null,
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
            'payload' => [
                'chunked' => false,
                'foo' => 'bar',
            ],
            'payload_hash' => 'hash-1',
            'sync_scope_hash' => null,
            'sync_batch_id' => null,
            'status' => 'new',
            'error_message' => null,
        ]);

        $extractor = new ApiImportPayloadExtractor;

        $this->assertSame(
            $record->payload,
            $extractor->reconstructPayload($record),
        );
    }

    public function test_it_reconstructs_list_strategy_by_merging_chunk_arrays(): void
    {
        $connection = ApiConnection::create([
            'name' => 'Test',
            'base_url' => 'https://example.test',
            'api_type' => 'REST',
            'auth_type' => 'JWT',
            'auth_credentials' => null,
            'headers' => null,
            'status' => 'New',
        ]);

        $endpoint = ApiEndpoint::create([
            'name' => 'Test Endpoint',
            'api_connection_id' => $connection->id,
            'path' => '/test',
            'method' => 'GET',
            'direct_access' => false,
            'variables' => [],
            'response_map' => [],
            'expected_response' => [],
            'field_mappings' => [],
            'transformers' => [],
            'lang_override' => null,
            'rate_limit' => null,
            'rate_window' => null,
            'status' => 'new',
            'timeout' => 30,
            'destination_model' => null,
            'key_fields' => null,
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
            'payload' => [
                'chunked' => true,
                'strategy' => 'list',
            ],
            'payload_hash' => 'hash-2',
            'sync_scope_hash' => null,
            'sync_batch_id' => null,
            'status' => 'new',
            'error_message' => null,
        ]);

        ApiImportPayloadChunk::create([
            'api_import_record_id' => $record->id,
            'chunk_index' => 0,
            'payload_chunk' => json_encode([['x' => 1]], JSON_UNESCAPED_SLASHES),
            'items_count' => 1,
            'bytes_size' => null,
        ]);

        ApiImportPayloadChunk::create([
            'api_import_record_id' => $record->id,
            'chunk_index' => 1,
            'payload_chunk' => json_encode([['x' => 2]], JSON_UNESCAPED_SLASHES),
            'items_count' => 1,
            'bytes_size' => null,
        ]);

        $extractor = new ApiImportPayloadExtractor;
        $reconstructed = $extractor->reconstructPayload($record);

        $this->assertSame(
            [
                ['x' => 1],
                ['x' => 2],
            ],
            $reconstructed
        );
    }

    public function test_it_reconstructs_non_list_strategy_by_concatenating_chunk_strings(): void
    {
        $connection = ApiConnection::create([
            'name' => 'Test',
            'base_url' => 'https://example.test',
            'api_type' => 'REST',
            'auth_type' => 'JWT',
            'auth_credentials' => null,
            'headers' => null,
            'status' => 'New',
        ]);

        $endpoint = ApiEndpoint::create([
            'name' => 'Test Endpoint',
            'api_connection_id' => $connection->id,
            'path' => '/test',
            'method' => 'GET',
            'direct_access' => false,
            'variables' => [],
            'response_map' => [],
            'expected_response' => [],
            'field_mappings' => [],
            'transformers' => [],
            'lang_override' => null,
            'rate_limit' => null,
            'rate_window' => null,
            'status' => 'new',
            'timeout' => 30,
            'destination_model' => null,
            'key_fields' => null,
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
            'payload' => [
                'chunked' => true,
            ],
            'payload_hash' => 'hash-3',
            'sync_scope_hash' => null,
            'sync_batch_id' => null,
            'status' => 'new',
            'error_message' => null,
        ]);

        // Valid JSON result after concatenation:
        // [{"a":1},{"a":2}]
        ApiImportPayloadChunk::create([
            'api_import_record_id' => $record->id,
            'chunk_index' => 0,
            'payload_chunk' => '[{"a":1},',
            'items_count' => null,
            'bytes_size' => null,
        ]);

        ApiImportPayloadChunk::create([
            'api_import_record_id' => $record->id,
            'chunk_index' => 1,
            'payload_chunk' => '{"a":2}]',
            'items_count' => null,
            'bytes_size' => null,
        ]);

        $extractor = new ApiImportPayloadExtractor;

        $this->assertSame(
            [
                ['a' => 1],
                ['a' => 2],
            ],
            $extractor->reconstructPayload($record)
        );
    }
}
