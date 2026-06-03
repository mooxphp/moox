<?php

declare(strict_types=1);

namespace Moox\Connect\Support;

use Moox\Connect\Models\ApiImportPayloadChunk;
use Moox\Connect\Models\ApiImportRecord;

final class ApiImportPayloadExtractor
{
    /**
     * Returns the reconstructed payload for "chunked" records.
     * - If record payload is not marked as chunked, returns the record payload meta as-is.
     * - If chunked and strategy == "list": merges JSON arrays from chunks into one array.
     * - Otherwise: concatenates chunk payload strings and JSON-decodes the result.
     *
     * @return array<mixed>
     */
    public function reconstructPayload(ApiImportRecord $record): array
    {
        $meta = $record->payload ?? [];

        if (! ($meta['chunked'] ?? false)) {
            return $meta;
        }

        $chunks = ApiImportPayloadChunk::query()
            ->where('api_import_record_id', $record->id)
            ->orderBy('chunk_index')
            ->pluck('payload_chunk')
            ->all();

        if (($meta['strategy'] ?? null) === 'list') {
            $items = [];

            foreach ($chunks as $json) {
                $data = json_decode((string) $json, true) ?? [];
                $items = array_merge($items, $data);
            }

            return $items;
        }

        $json = implode('', array_map(static fn (mixed $c): string => (string) $c, $chunks));

        return json_decode($json, true) ?? [];
    }

    public function decodeChunkPayload(ApiImportPayloadChunk $chunk): mixed
    {
        return $this->decodePayloadChunkValue((string) $chunk->payload_chunk);
    }

    public function decodePayloadChunkValue(string $payloadChunk): mixed
    {
        $decoded = json_decode($payloadChunk, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $payloadChunk;
    }
}
