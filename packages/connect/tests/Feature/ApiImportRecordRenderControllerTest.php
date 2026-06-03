<?php

declare(strict_types=1);

namespace Moox\Connect\Tests\Feature;

use Illuminate\Http\Request;
use Moox\Connect\Http\Controllers\ApiImportRecordRenderController;
use Moox\Connect\Models\ApiConnection;
use Moox\Connect\Models\ApiEndpoint;
use Moox\Connect\Models\ApiImportPayloadChunk;
use Moox\Connect\Models\ApiImportRecord;
use Moox\Connect\Support\ApiImportPayloadExtractor;
use Moox\Connect\Tests\TestCase;

final class ApiImportRecordRenderControllerTest extends TestCase
{
    public function test_it_renders_record_chunks_and_linked_records(): void
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

        $endpointMain = ApiEndpoint::create([
            'name' => 'Main Endpoint',
            'api_connection_id' => $connection->id,
            'path' => '/main',
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

        $endpointLinked = ApiEndpoint::create([
            'name' => 'Linked Endpoint',
            'api_connection_id' => $connection->id,
            'path' => '/linked',
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

        $linkedRecord = ApiImportRecord::create([
            'api_connection_id' => $connection->id,
            'api_endpoint_id' => $endpointLinked->id,
            'external_key' => 'lk-1',
            'sync_scope_hash' => null,
            'sync_batch_id' => null,
            'payload' => [
                'chunked' => false,
                'foo' => 'linked',
            ],
            'payload_hash' => 'hash-linked',
            'status' => 'new',
            'error_message' => null,
        ]);

        $mainRecord = ApiImportRecord::create([
            'api_connection_id' => $connection->id,
            'api_endpoint_id' => $endpointMain->id,
            'external_key' => null,
            'sync_scope_hash' => null,
            'sync_batch_id' => null,
            'payload' => [
                'chunked' => true,
                'strategy' => 'list',
            ],
            'payload_hash' => 'hash-main',
            'status' => 'new',
            'error_message' => null,
        ]);

        // Reconstructed payload will be:
        // [
        //   ['external_id' => 'lk-1', 'foo' => 'bar'],
        //   ['external_id' => 'lk-2']
        // ]
        ApiImportPayloadChunk::create([
            'api_import_record_id' => $mainRecord->id,
            'chunk_index' => 0,
            'payload_chunk' => json_encode([
                [
                    'external_id' => 'lk-1',
                    'foo' => 'bar',
                ],
            ], JSON_UNESCAPED_SLASHES),
            'items_count' => 1,
            'bytes_size' => null,
        ]);

        ApiImportPayloadChunk::create([
            'api_import_record_id' => $mainRecord->id,
            'chunk_index' => 1,
            'payload_chunk' => json_encode([
                [
                    'external_id' => 'lk-2',
                ],
            ], JSON_UNESCAPED_SLASHES),
            'items_count' => 1,
            'bytes_size' => null,
        ]);

        $controller = new ApiImportRecordRenderController;
        $request = Request::create('/connect/import-records/'.$mainRecord->id, 'GET', [
            'max_chars' => 2000,
            'max_chunks' => 10,
            'max_linked' => 10,
        ]);

        $extractor = new ApiImportPayloadExtractor;

        $view = $controller->show($request, $mainRecord, $extractor);

        $data = $view->getData();
        self::assertSame($mainRecord->id, $data['apiImportRecord']->id);

        $linked = $data['linkedRecordsForView'] ?? [];
        self::assertNotEmpty($linked);

        $linkedIds = array_map(static fn (array $row): int => (int) $row['id'], $linked);
        self::assertContains((int) $linkedRecord->id, $linkedIds);
    }
}
