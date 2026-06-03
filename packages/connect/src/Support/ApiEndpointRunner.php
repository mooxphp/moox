<?php

declare(strict_types=1);

namespace Moox\Connect\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Moox\Connect\Models\ApiConnection;
use Moox\Connect\Models\ApiEndpoint;
use Moox\Connect\Models\ApiImportPayloadChunk;
use Moox\Connect\Models\ApiImportRecord;
use Moox\Connect\Models\ApiLog;

class ApiEndpointRunner
{
    // Max. Größe (in Bytes), ab der wir beginnen zu chunken (ca. 512 KB)
    private const MAX_INLINE_BYTES = 524288;

    // Max. Anzahl Elemente pro Chunk, wenn das Payload ein Listen-Array ist
    private const MAX_ITEMS_PER_CHUNK = 50;

    /**
     * Hard cap to avoid long-running queue jobs on flaky upstream APIs.
     */
    private const MAX_REQUEST_TIMEOUT_SECONDS = 120;

    private const MIN_REQUEST_TIMEOUT_SECONDS = 5;

    public function run(
        ApiEndpoint $endpoint,
        ?string $syncBatchId = null,
        bool $throwOnFailure = false,
        ?string $externalKey = null,
        ?string $syncScopeHash = null,
    ): array {
        $connection = $endpoint->apiConnection;

        // Identity of the import record must remain stable even when $syncScopeHash is null.
        $identityScopeHash = ApiImportRecord::resolveIdentityScopeHash($syncScopeHash, $externalKey);

        // Auth-Header über bestehende Logic holen
        /** @var ConnectionHealthChecker $checker */
        $checker = app(ConnectionHealthChecker::class);
        $authHeaders = (new \ReflectionClass($checker))
            ->getMethod('buildAuthHeaders')
            ->invoke($checker, $connection);

        $headers = array_merge(
            $connection->headers ?? [],
            $authHeaders
        );

        $url = rtrim($connection->base_url, '/').'/'.ltrim($endpoint->path, '/');

        $method = strtoupper($endpoint->method);

        $timeoutSeconds = $this->resolveTimeoutSeconds($connection, $endpoint);
        $request = Http::withHeaders($headers)
            ->connectTimeout(10)
            ->timeout($timeoutSeconds)
            ->retry(
                [200, 500, 1000],
                static function (\Throwable $exception): bool {
                    return $exception instanceof ConnectionException;
                },
                throw: false
            );

        try {
            $response = match ($method) {
                'POST' => $request->post($url, $endpoint->variables ?? []),
                'PUT' => $request->put($url, $endpoint->variables ?? []),
                'PATCH' => $request->patch($url, $endpoint->variables ?? []),
                'DELETE' => $request->delete($url, $endpoint->variables ?? []),
                default => $request->get($url, $endpoint->variables ?? []),
            };

            // Bei Auth-Fehlern (401/403) einmal Token-Refresh + Retry versuchen.
            if (in_array($response->status(), [401, 403], true)) {
                $refreshedHeaders = $this->refreshAuthHeadersForRetry($connection);

                if ($refreshedHeaders !== []) {
                    $retryRequest = Http::withHeaders(array_merge($connection->headers ?? [], $refreshedHeaders))
                        ->connectTimeout(10)
                        ->timeout($timeoutSeconds)
                        ->retry(
                            [200, 500, 1000],
                            static function (\Throwable $exception): bool {
                                return $exception instanceof ConnectionException;
                            },
                            throw: false
                        );

                    $retried = match ($method) {
                        'POST' => $retryRequest->post($url, $endpoint->variables ?? []),
                        'PUT' => $retryRequest->put($url, $endpoint->variables ?? []),
                        'PATCH' => $retryRequest->patch($url, $endpoint->variables ?? []),
                        'DELETE' => $retryRequest->delete($url, $endpoint->variables ?? []),
                        default => $retryRequest->get($url, $endpoint->variables ?? []),
                    };

                    $connection->logs()->create([
                        'trigger' => 'SYSTEM',
                        'status_code' => (string) $retried->status(),
                        'request_data' => [
                            'type' => 'endpoint_auth_retry',
                            'url' => $url,
                            'method' => $method,
                            'retry_after_auth_error' => true,
                            'initial_status' => $response->status(),
                        ],
                        'response_data' => [
                            'body' => $retried->json() ?? $retried->body(),
                        ],
                        'error_message' => $retried->successful()
                            ? null
                            : 'Auth retry failed (status '.$retried->status().')',
                    ]);

                    $response = $retried;
                }
            }
        } catch (ConnectionException $e) {
            if ($this->isTimeoutException($e) && $timeoutSeconds < self::MAX_REQUEST_TIMEOUT_SECONDS) {
                $retryTimeout = self::MAX_REQUEST_TIMEOUT_SECONDS;
                $retryRequest = Http::withHeaders($headers)
                    ->connectTimeout(10)
                    ->timeout($retryTimeout)
                    ->retry(
                        [500, 1500, 3000],
                        static function (\Throwable $exception): bool {
                            return $exception instanceof ConnectionException;
                        },
                        throw: false
                    );

                try {
                    $response = match ($method) {
                        'POST' => $retryRequest->post($url, $endpoint->variables ?? []),
                        'PUT' => $retryRequest->put($url, $endpoint->variables ?? []),
                        'PATCH' => $retryRequest->patch($url, $endpoint->variables ?? []),
                        'DELETE' => $retryRequest->delete($url, $endpoint->variables ?? []),
                        default => $retryRequest->get($url, $endpoint->variables ?? []),
                    };

                    // Continue with normal success/failure handling below.
                    goto request_completed;
                } catch (ConnectionException) {
                    // Fall through to the standard failed-record path.
                }
            }

            $errorMessage = (string) $e->getMessage();
            $shortError = mb_substr($errorMessage, 0, 240);
            $payloadHash = hash('sha256', $errorMessage);

            if ($externalKey !== null && $externalKey !== '') {
                $importRecord = ApiImportRecord::withTrashed()->updateOrCreate(
                    [
                        'api_connection_id' => $connection->id,
                        'api_endpoint_id' => $endpoint->id,
                        'sync_scope_hash' => $identityScopeHash,
                        'external_key' => $externalKey,
                    ],
                    [
                        'sync_batch_id' => $syncBatchId,
                        'payload' => [],
                        'payload_hash' => $payloadHash,
                        'status' => 'failed',
                        'error_message' => $shortError,
                        'deleted_at' => null,
                    ]
                );
            } else {
                $importRecord = ApiImportRecord::create([
                    'api_connection_id' => $connection->id,
                    'api_endpoint_id' => $endpoint->id,
                    'external_key' => null,
                    'sync_scope_hash' => $identityScopeHash,
                    'sync_batch_id' => $syncBatchId,
                    'payload' => [],
                    'payload_hash' => $payloadHash,
                    'status' => 'failed',
                    'error_message' => $shortError,
                ]);
            }

            ApiLog::create([
                'api_connection_id' => $connection->id,
                'endpoint_id' => $endpoint->id,
                'trigger' => 'USER',
                'request_data' => [
                    'type' => 'endpoint_run',
                    'url' => $url,
                    'method' => $method,
                    'headers' => $headers,
                    'variables' => $endpoint->variables ?? [],
                ],
                'response_data' => null,
                'status_code' => '0',
                'error_message' => $shortError,
            ]);

            if ($throwOnFailure) {
                throw $e;
            }

            return [
                'status' => 0,
                'headers' => [],
                'body' => null,
                'import_record_id' => $importRecord->id,
            ];
        }

        request_completed:
        $body = $response->json() ?? $response->body();

        // Payload für Speicherung vorbereiten und dabei ungültige UTF-8-Zeichen ersetzen
        $rawPayload = is_array($body) ? $body : ['body' => $body];
        $normalizedPayloadJson = json_encode($rawPayload, JSON_INVALID_UTF8_SUBSTITUTE);
        $normalizedPayload = json_decode($normalizedPayloadJson, true);

        $hash = hash('sha256', $normalizedPayloadJson);

        // Fehlertext auf UTF-8 normalisieren, damit MySQL ihn speichern kann
        $rawError = $response->body();
        $normalizedError = $rawError !== null
            ? mb_convert_encoding((string) $rawError, 'UTF-8', 'UTF-8,ISO-8859-1,ISO-8859-15')
            : null;

        // Basis-Import-Record ohne volles Payload – das kommt gleich per Regel/Chunking dazu.
        // Für Detail-Endpoints mit externer ID upserten wir auf Identität statt immer neue Zeilen zu erzeugen.

        if ($externalKey !== null && $externalKey !== '') {
            /** @var ApiImportRecord|null $existingImportRecord */
            $existingImportRecord = ApiImportRecord::withTrashed()
                ->where('api_connection_id', $connection->id)
                ->where('api_endpoint_id', $endpoint->id)
                ->where('sync_scope_hash', $identityScopeHash)
                ->where('external_key', $externalKey)
                ->first();

            $wasUpToDate = $existingImportRecord !== null
                && strtolower((string) $existingImportRecord->status) === 'processed'
                && (string) $existingImportRecord->payload_hash === (string) $hash;

            $statusAfterFetch = $response->successful()
                ? ($wasUpToDate
                    ? 'processed'
                    : ($existingImportRecord !== null ? 'update' : 'fetched'))
                : 'failed';

            $importRecord = ApiImportRecord::withTrashed()->updateOrCreate(
                [
                    'api_connection_id' => $connection->id,
                    'api_endpoint_id' => $endpoint->id,
                    'sync_scope_hash' => $identityScopeHash,
                    'external_key' => $externalKey,
                ],
                [
                    'sync_batch_id' => $syncBatchId,
                    'payload' => [],
                    'payload_hash' => $hash,
                    'status' => $statusAfterFetch,
                    'error_message' => $response->successful() ? null : $normalizedError,
                    'deleted_at' => null,
                ]
            );
        } else {
            $statusAfterFetch = $response->successful() ? 'fetched' : 'failed';
            $importRecord = ApiImportRecord::create([
                'api_connection_id' => $connection->id,
                'api_endpoint_id' => $endpoint->id,
                'external_key' => null,
                'sync_scope_hash' => $identityScopeHash,
                'sync_batch_id' => $syncBatchId,
                'payload' => [],
                'payload_hash' => $hash,
                'status' => $statusAfterFetch,
                'error_message' => $response->successful() ? null : $normalizedError,
            ]);
        }

        $totalBytes = strlen($normalizedPayloadJson);

        DB::transaction(function () use ($importRecord, $normalizedPayload, $normalizedPayloadJson, $totalBytes): void {
            // Bei Updates alte Chunks physisch entfernen (soft delete reicht wegen unique-index nicht).
            ApiImportPayloadChunk::withTrashed()
                ->where('api_import_record_id', $importRecord->id)
                ->forceDelete();

            // Regel 1: Wenn Payload klein genug ist, direkt inline im Record speichern
            if ($totalBytes <= self::MAX_INLINE_BYTES) {
                $importRecord->payload = $normalizedPayload;
                $importRecord->save();

                return;
            }

            // Regel 2: Großes Payload – in Chunks in eigene Tabelle auslagern
            $isList = is_array($normalizedPayload)
                && (function_exists('array_is_list')
                    ? array_is_list($normalizedPayload)
                    : array_keys($normalizedPayload) === range(0, count($normalizedPayload) - 1));

            $totalItems = $isList ? count($normalizedPayload) : null;
            $chunkRows = [];
            $chunkIndex = 0;
            $now = now();

            if ($isList && $totalItems > self::MAX_ITEMS_PER_CHUNK) {
                // Chunking nach Anzahl Elemente (z.B. 50 Items pro Chunk)
                $chunks = array_chunk($normalizedPayload, self::MAX_ITEMS_PER_CHUNK);

                foreach ($chunks as $chunk) {
                    $chunkJson = json_encode($chunk, JSON_INVALID_UTF8_SUBSTITUTE);
                    $chunkRows[] = [
                        'api_import_record_id' => $importRecord->id,
                        'chunk_index' => $chunkIndex++,
                        'payload_chunk' => $chunkJson,
                        'items_count' => count($chunk),
                        'bytes_size' => strlen($chunkJson),
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ];
                }

                ApiImportPayloadChunk::query()->upsert(
                    $chunkRows,
                    ['api_import_record_id', 'chunk_index'],
                    ['payload_chunk', 'items_count', 'bytes_size', 'updated_at', 'deleted_at']
                );

                $importRecord->payload = [
                    'chunked' => true,
                    'strategy' => 'list',
                    'total_items' => $totalItems,
                    'chunks' => $chunkIndex,
                ];
                $importRecord->save();

                return;
            }

            // Fallback: Chunking nach Byte-Größe
            $chunkSize = 256 * 1024; // 256 KB
            $chunks = str_split($normalizedPayloadJson, $chunkSize);

            foreach ($chunks as $chunk) {
                $chunkRows[] = [
                    'api_import_record_id' => $importRecord->id,
                    'chunk_index' => $chunkIndex++,
                    'payload_chunk' => $chunk,
                    'items_count' => null,
                    'bytes_size' => strlen($chunk),
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }

            ApiImportPayloadChunk::query()->upsert(
                $chunkRows,
                ['api_import_record_id', 'chunk_index'],
                ['payload_chunk', 'items_count', 'bytes_size', 'updated_at', 'deleted_at']
            );

            $importRecord->payload = [
                'chunked' => true,
                'strategy' => 'bytes',
                'total_bytes' => $totalBytes,
                'chunks' => $chunkIndex,
            ];
            $importRecord->save();
        });

        // Für ApiLog muss error_message in VARCHAR(255) passen
        $shortError = $normalizedError;
        if ($shortError !== null && mb_strlen($shortError) > 240) {
            $shortError = mb_substr($shortError, 0, 240).'…';
        }

        // Zusätzlich in ApiLog schreiben (mit kleiner Preview statt vollem Payload)
        ApiLog::create([
            'api_connection_id' => $connection->id,
            'endpoint_id' => $endpoint->id,
            'trigger' => 'USER',
            'request_data' => [
                'type' => 'endpoint_run',
                'url' => $url,
                'method' => $method,
                'headers' => $headers,
                'variables' => $endpoint->variables ?? [],
            ],
            'response_data' => $totalBytes <= self::MAX_INLINE_BYTES
                ? $normalizedPayload
                : ['chunked' => true, 'preview' => array_slice((array) $normalizedPayload, 0, 3, true)],
            'status_code' => (string) $response->status(),
            'error_message' => $response->successful() ? null : $shortError,
        ]);

        if ($throwOnFailure && ! $response->successful()) {
            throw new RequestException($response);
        }

        return [
            'status' => $response->status(),
            'headers' => $response->headers(),
            'body' => $body,
            'import_record_id' => $importRecord->id,
        ];
    }

    private function refreshAuthHeadersForRetry(ApiConnection $connection): array
    {
        $authType = strtolower((string) ($connection->auth_type ?? ''));
        $creds = $connection->auth_credentials ?? [];

        if ($authType !== 'jwt') {
            return [];
        }

        $loginMethod = (string) ($connection->login_method ?? '');
        if ($loginMethod === 'none' || $loginMethod === 'direct_token') {
            return [];
        }

        $accessTokenKey = $creds['access_token_key'] ?? 'access_token';
        unset($creds[$accessTokenKey]);
        $connection->auth_credentials = $creds;
        $connection->saveQuietly();

        /** @var ConnectionHealthChecker $checker */
        $checker = app(ConnectionHealthChecker::class);

        return (new \ReflectionClass($checker))
            ->getMethod('buildAuthHeaders')
            ->invoke($checker, $connection);
    }

    private function resolveTimeoutSeconds(ApiConnection $connection, ApiEndpoint $endpoint): int
    {
        $configured = (int) ($endpoint->timeout ?? $connection->timeout ?? 30);
        if ($configured <= 0) {
            $configured = 30;
        }

        return max(
            self::MIN_REQUEST_TIMEOUT_SECONDS,
            min(self::MAX_REQUEST_TIMEOUT_SECONDS, $configured)
        );
    }

    private function isTimeoutException(ConnectionException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'timed out')
            || str_contains($message, 'cURL error 28');
    }
}
