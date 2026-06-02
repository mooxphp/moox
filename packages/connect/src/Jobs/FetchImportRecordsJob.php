<?php

declare(strict_types=1);

namespace Moox\Connect\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Moox\Connect\Models\ApiConnection;
use Moox\Connect\Models\ApiEndpoint;
use Moox\Connect\Models\ApiImportRecord;
use Moox\Connect\Models\ApiLog;
use Throwable;

final class FetchImportRecordsJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Max. rows per INSERT ... ON DUPLICATE KEY UPDATE on api_import_records.
     */
    private const IMPORT_RECORD_WRITE_CHUNK_SIZE = 100;

    private const DEADLOCK_RETRY_ATTEMPTS = 3;

    public function __construct(
        private int $endpointId,
        private array $parameters = []
    ) {}

    public function handle(): void
    {
        /** @var ApiEndpoint $endpoint */
        $endpoint = ApiEndpoint::query()->with('apiConnection')->findOrFail($this->endpointId);
        /** @var ApiConnection $connection */
        $connection = $endpoint->apiConnection;

        $client = $connection->client();

        $response = method_exists($client, 'executeEndpointWithMeta')
            ? $client->executeEndpointWithMeta($endpoint, $this->parameters)
            : [
                'status' => 200,
                'body' => $client->executeEndpoint($endpoint, $this->parameters),
                'headers' => [],
            ];

        $status = (int) ($response['status'] ?? 0);
        $body = $response['body'] ?? null;

        if ($status < 200 || $status >= 300) {
            $this->recordFailedFetch($connection, $endpoint, $status, $body);

            return;
        }

        $items = $this->extractItemsFromResponse($endpoint, $body);

        if (empty($items)) {
            return;
        }

        $externalKeyField = $endpoint->external_key_field ?: null;
        $syncMode = $endpoint->sync_mode ?: 'append';
        $syncScopeFields = $endpoint->sync_scope_fields ?? [];
        $syncBatchId = (string) Str::uuid();

        $this->runWithDeadlockRetry(function () use ($items, $connection, $endpoint, $externalKeyField, $syncMode, $syncScopeFields, $syncBatchId): void {
            DB::transaction(function () use ($items, $connection, $endpoint, $externalKeyField, $syncMode, $syncScopeFields, $syncBatchId): void {
                // Für Prune: Keys je Scope sammeln (scope_hash => [keys...])
                $seen = [];

                /** @var list<array<string, mixed>> $rowsToUpsert */
                $rowsToUpsert = [];

                foreach ($items as $item) {
                    $payload = is_array($item) ? $item : (array) $item;

                    $payloadHash = hash('sha256', json_encode($payload));

                    $externalKey = $externalKeyField !== null
                        ? Arr::get($payload, $externalKeyField)
                        : null;

                    if ($externalKey === null || $externalKey === '') {
                        // Ohne stabile ID kein Sync/Upsert möglich
                        continue;
                    }

                    $scope = [];
                    foreach ((array) $syncScopeFields as $internal => $externalPath) {
                        $scope[$internal] = Arr::get($payload, $externalPath);
                    }

                    // Wenn Scope-Felder konfiguriert sind, aber nicht befüllt werden können, skippen
                    if ($syncScopeFields && in_array(null, $scope, true)) {
                        continue;
                    }

                    $scopeHash = $scope === []
                        ? null
                        : hash('sha256', json_encode($scope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    $identityScopeHash = ApiImportRecord::resolveIdentityScopeHash($scopeHash, (string) $externalKey);

                    $seen[$identityScopeHash ?? '__null'][] = (string) $externalKey;

                    $rowsToUpsert[] = [
                        'api_connection_id' => $connection->id,
                        'api_endpoint_id' => $endpoint->id,
                        'sync_scope_hash' => $identityScopeHash,
                        'external_key' => (string) $externalKey,
                        'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'payload_hash' => $payloadHash,
                        'sync_batch_id' => $syncBatchId,
                        'status' => 'fetched',
                        'error_message' => null,
                        'deleted_at' => null,
                    ];
                }

                $uniqueBy = [
                    'api_connection_id',
                    'api_endpoint_id',
                    'sync_scope_hash',
                    'external_key',
                ];

                $updateColumns = [
                    'payload',
                    'payload_hash',
                    'sync_batch_id',
                    'status',
                    'error_message',
                    'deleted_at',
                ];

                foreach (array_chunk($rowsToUpsert, self::IMPORT_RECORD_WRITE_CHUNK_SIZE) as $chunk) {
                    if ($chunk === []) {
                        continue;
                    }

                    ApiImportRecord::upsert($chunk, $uniqueBy, $updateColumns);
                }

                if ($syncMode !== 'sync') {
                    return;
                }

                // Prune nur, wenn wir den kompletten Scope geladen haben (vollständiges Ergebnis für den Scope)
                foreach ($seen as $scopeKey => $keys) {
                    $keys = array_values(array_unique($keys));
                    if ($keys === []) {
                        continue;
                    }

                    $q = ApiImportRecord::query()
                        ->where('api_connection_id', $connection->id)
                        ->where('api_endpoint_id', $endpoint->id);

                    if ($scopeKey === '__null') {
                        $q->whereNull('sync_scope_hash');
                    } else {
                        $q->where('sync_scope_hash', $scopeKey);
                    }

                    $q->whereNotIn('external_key', $keys)->delete();
                }
            });
        });

        // Start transform stage for this specific fetch batch.
        // The transform job uses claim-locking, so it is safe with parallel workers.
        TransformImportRecordsJob::dispatch(
            $endpoint->id,
            syncBatchId: $syncBatchId
        );
    }

    /**
     * Versucht anhand von response_map die Liste von Items aus der Response zu extrahieren.
     */
    private function extractItemsFromResponse(ApiEndpoint $endpoint, mixed $response): array
    {
        $data = $response;

        if (is_object($response) && method_exists($response, 'json')) {
            $data = $response->json();
        }

        if (! is_array($data)) {
            return [];
        }

        $map = $endpoint->response_map ?? null;

        if (is_array($map) && isset($map['items_path']) && is_string($map['items_path'])) {
            $items = Arr::get($data, $map['items_path'], []);

            return is_array($items) ? $items : [];
        }

        // Fallback: wenn das oberste Array bereits die Items enthält
        if (array_is_list($data)) {
            return $data;
        }

        // weitere Fallbacks: erstes Array-Feld suchen
        foreach ($data as $value) {
            if (is_array($value) && array_is_list($value)) {
                return $value;
            }
        }

        return [];
    }

    private function recordFailedFetch(ApiConnection $connection, ApiEndpoint $endpoint, int $status, mixed $body): void
    {
        $payload = is_array($body) ? $body : ['body' => $body];
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

        ApiImportRecord::query()->create([
            'api_connection_id' => $connection->id,
            'api_endpoint_id' => $endpoint->id,
            'external_key' => null,
            'sync_scope_hash' => null,
            'payload' => json_decode($payloadJson, true),
            'payload_hash' => hash('sha256', $payloadJson),
            'sync_batch_id' => (string) Str::uuid(),
            'status' => 'failed',
            'error_message' => mb_substr((string) ($payload['body'] ?? $payloadJson), 0, 1000),
        ]);

        ApiLog::create([
            'api_connection_id' => $connection->id,
            'endpoint_id' => $endpoint->id,
            'trigger' => 'SYSTEM',
            'request_data' => [
                'type' => 'job',
                'job' => self::class,
                'parameters' => $this->parameters,
            ],
            'response_data' => $payload,
            'status_code' => (string) max($status, 0),
            'error_message' => 'Fetch endpoint returned a non-success HTTP status.',
        ]);
    }

    private function runWithDeadlockRetry(\Closure $callback): void
    {
        $attempt = 0;

        beginning:
        try {
            $callback();
        } catch (Throwable $exception) {
            if (! $this->isDeadlockException($exception) || $attempt >= self::DEADLOCK_RETRY_ATTEMPTS - 1) {
                throw $exception;
            }

            usleep((int) (100000 * (2 ** $attempt)));
            $attempt++;

            goto beginning;
        }
    }

    private function isDeadlockException(Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'deadlock found')
            || str_contains($message, 'serialization failure')
            || (string) $exception->getCode() === '40001';
    }
}
