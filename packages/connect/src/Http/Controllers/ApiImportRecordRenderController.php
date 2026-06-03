<?php

declare(strict_types=1);

namespace Moox\Connect\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Moox\Connect\Jobs\RunEndpointForItemJob;
use Moox\Connect\Models\ApiEndpoint;
use Moox\Connect\Models\ApiImportPayloadChunk;
use Moox\Connect\Models\ApiImportRecord;
use Moox\Connect\Models\ApiLog;
use Moox\Connect\Support\ApiImportPayloadExtractor;
use Moox\Connect\Support\QueueJobStatsService;

final class ApiImportRecordRenderController
{
    public function showParentEndpoint(Request $request, int $parentEndpointId): View
    {
        $parentEndpoint = ApiEndpoint::query()->findOrFail($parentEndpointId);
        $endpointIds = $this->collectEndpointTreeIds((int) $parentEndpoint->id);

        $recordsQuery = ApiImportRecord::query()
            ->with('apiEndpoint')
            ->orderByDesc('id');

        $parentKey = $request->query('parent_key');
        if (is_string($parentKey) && $parentKey !== '') {
            $recordsQuery->where('sync_scope_hash', hash('sha256', $parentKey));
        } else {
            $parentKey = null;
            $recordsQuery->whereIn('api_endpoint_id', $endpointIds);
        }

        $records = $recordsQuery->get();
        $overview = $this->buildOverviewFromRecords($records);
        $stats = $this->buildOverviewStats($overview);

        // dd($overview,$stats,$parentEndpoint );
        return view('connect::api-import-record-parent-overview', [
            'parentKey' => $parentKey,
            'overview' => $overview,
            'stats' => $stats,
            'parentEndpoint' => [
                'id' => $parentEndpoint->id,
                'name' => $parentEndpoint->name,
                'method' => $parentEndpoint->method,
                'path' => $parentEndpoint->path,
            ],
        ]);
    }

    private function buildOverviewFromRecords(Collection $records): Collection
    {
        $endpointIds = $records->pluck('api_endpoint_id')->filter()->unique()->values()->all();
        $apiConnectionId = $records->first()?->api_connection_id;
        $queueStats = app(QueueJobStatsService::class)->summarizeRunEndpointForItemJobsByEndpoint();

        $latestLogByEndpoint = collect();
        if ($apiConnectionId && $endpointIds !== []) {
            $latestLogByEndpoint = ApiLog::query()
                ->where('api_connection_id', $apiConnectionId)
                ->whereIn('endpoint_id', $endpointIds)
                ->orderByDesc('id')
                ->get(['id', 'endpoint_id', 'status_code', 'error_message', 'created_at'])
                ->unique('endpoint_id')
                ->keyBy('endpoint_id');
        }

        return $records->groupBy('api_endpoint_id')->map(
            function (Collection $endpointRecords, int|string $endpointId) use ($latestLogByEndpoint, $queueStats): array {
                /** @var ApiImportRecord|null $first */
                $first = $endpointRecords->first();
                $latestLog = $latestLogByEndpoint->get((int) $endpointId);
                $statusCode = is_object($latestLog) ? (string) ($latestLog->status_code ?? '') : '';
                $routeMethod = $first?->apiEndpoint?->method;
                $routePath = $first?->apiEndpoint?->path;
                $queuedJobsForRoute = (int) ($queueStats['by_endpoint'][(int) $endpointId] ?? 0);
                /** @var ApiImportRecord|null $latestFailedRecord */
                $latestFailedRecord = $endpointRecords
                    ->filter(function (ApiImportRecord $record): bool {
                        return strtolower((string) ($record->status ?? '')) === 'failed';
                    })
                    ->sortByDesc('id')
                    ->first();

                $externalRouteCounts = $endpointRecords
                    ->groupBy(function (ApiImportRecord $record): string {
                        return is_string($record->external_key) && $record->external_key !== ''
                            ? $record->external_key
                            : '__null__';
                    })
                    ->map(function (Collection $recordsByKey) use ($routeMethod, $routePath): array {
                        /** @var ApiImportRecord|null $firstByKey */
                        $firstByKey = $recordsByKey->first();
                        $externalKey = is_string($firstByKey?->external_key) && $firstByKey->external_key !== ''
                            ? $firstByKey->external_key
                            : null;

                        return [
                            'external_key' => $externalKey,
                            'route_method' => $routeMethod,
                            'route_path' => $routePath,
                            'count' => $recordsByKey->count(),
                        ];
                    })
                    ->sortByDesc('count')
                    ->values()
                    ->all();

                return [
                    'endpoint_id' => (int) $endpointId,
                    'endpoint_name' => $first?->apiEndpoint?->name,
                    'endpoint_path' => $routePath,
                    'endpoint_method' => $routeMethod,
                    'queue_name' => (string) ($queueStats['queue'] ?? config('queue.default', 'default')),
                    'job_class' => RunEndpointForItemJob::class,
                    'queued_jobs_count' => $queuedJobsForRoute,
                    'latest_log_status_code' => $statusCode !== '' ? $statusCode : null,
                    'latest_log_ok' => $statusCode !== '' ? str_starts_with($statusCode, '2') : null,
                    'latest_log_error' => is_object($latestLog) ? $latestLog->error_message : null,
                    'latest_log_created_at' => is_object($latestLog) ? $latestLog->created_at?->toDateTimeString() : null,
                    'latest_failed_record_id' => $latestFailedRecord?->id,
                    'latest_failed_external_route' => is_string($latestFailedRecord?->external_key) && $latestFailedRecord->external_key !== ''
                        ? route('connect.import-records.show', ['externalKey' => $latestFailedRecord->external_key])
                        : null,
                    'external_route_counts' => $externalRouteCounts,
                    'records' => $endpointRecords->map(function (ApiImportRecord $record): array {
                        return [
                            'id' => $record->id,
                            'external_key' => $record->external_key,
                            'status' => $record->status,
                            'created_at' => $record->created_at?->toDateTimeString(),
                            'show_by_external' => is_string($record->external_key) && $record->external_key !== ''
                                ? route('connect.import-records.show', ['externalKey' => $record->external_key])
                                : null,
                        ];
                    })->values()->all(),
                ];
            }
        )->values();
    }

    /**
     * @param Collection<int, array{
     *   endpoint_id: int,
     *   latest_log_ok: bool|null,
     *   records: array<int, array{id:int, status:string|null}>
     * }> $overview
     * @return array{
     *   endpoints_total: int,
     *   endpoints_ok: int,
     *   endpoints_error: int,
     *   endpoints_unknown: int,
     *   records_total: int,
     *   records_failed: int,
     *   records_processed: int,
     *   records_new: int,
     *   queue_name: string,
     *   job_class: string,
     *   queued_jobs_total: int
     * }
     */
    private function buildOverviewStats(Collection $overview): array
    {
        $queueStats = app(QueueJobStatsService::class)->summarizeRunEndpointForItemJobsByEndpoint();
        $endpointsTotal = $overview->count();
        $endpointsOk = 0;
        $endpointsError = 0;
        $endpointsUnknown = 0;
        $recordsTotal = 0;
        $recordsFailed = 0;
        $recordsProcessed = 0;
        $recordsNew = 0;

        foreach ($overview as $endpoint) {
            $latestLogOk = $endpoint['latest_log_ok'] ?? null;
            if ($latestLogOk === true) {
                $endpointsOk++;
            } elseif ($latestLogOk === false) {
                $endpointsError++;
            } else {
                $endpointsUnknown++;
            }

            $records = $endpoint['records'] ?? [];
            $recordsTotal += count($records);
            foreach ($records as $record) {
                $status = strtolower((string) ($record['status'] ?? ''));
                if ($status === 'failed') {
                    $recordsFailed++;
                } elseif ($status === 'processed') {
                    $recordsProcessed++;
                } elseif ($status === 'new' || $status === 'fetched' || $status === 'update') {
                    $recordsNew++;
                }
            }
        }

        return [
            'endpoints_total' => $endpointsTotal,
            'endpoints_ok' => $endpointsOk,
            'endpoints_error' => $endpointsError,
            'endpoints_unknown' => $endpointsUnknown,
            'records_total' => $recordsTotal,
            'records_failed' => $recordsFailed,
            'records_processed' => $recordsProcessed,
            'records_new' => $recordsNew,
            'queue_name' => (string) ($queueStats['queue'] ?? config('queue.default', 'default')),
            'job_class' => RunEndpointForItemJob::class,
            'queued_jobs_total' => (int) ($queueStats['total'] ?? 0),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function collectEndpointTreeIds(int $parentEndpointId): array
    {
        $all = [$parentEndpointId => true];
        $frontier = [$parentEndpointId];

        while ($frontier !== []) {
            $children = ApiEndpoint::query()
                ->whereIn('parent_endpoint_id', $frontier)
                ->pluck('id')
                ->all();

            $frontier = [];
            foreach ($children as $childId) {
                $childId = (int) $childId;
                if (! isset($all[$childId])) {
                    $all[$childId] = true;
                    $frontier[] = $childId;
                }
            }
        }

        return array_map('intval', array_keys($all));
    }

    public function showByExternalKey(
        Request $request,
        string $externalKey,
        ApiImportPayloadExtractor $extractor
    ): View {
        $scopeHash = hash('sha256', $externalKey);

        // Avoid OR + ORDER BY on huge table to prevent MySQL filesort memory errors.
        $apiImportRecord = ApiImportRecord::query()
            ->where('sync_scope_hash', $scopeHash)
            ->orderByDesc('id')
            ->first();

        if (! $apiImportRecord) {
            $apiImportRecord = ApiImportRecord::query()
                ->where('external_key', $externalKey)
                ->orderByDesc('id')
                ->firstOrFail();
        }

        return $this->renderRecord($request, $apiImportRecord, $extractor);
    }

    private function renderRecord(
        Request $request,
        ApiImportRecord $apiImportRecord,
        ApiImportPayloadExtractor $extractor
    ): View {
        $maxChars = $this->clampMaxChars($request->query('max_chars'), 5000, 200000);
        $maxChunks = $this->clampMaxChunks($request->query('max_chunks'), 20, 200);

        $recordPayload = $apiImportRecord->payload ?? [];
        $recordPayloadChunked = $recordPayload['chunked'] ?? null;
        $recordPayloadStrategy = $recordPayload['strategy'] ?? null;

        $chunksCountAll = ApiImportPayloadChunk::query()
            ->where('api_import_record_id', $apiImportRecord->id)
            ->count();

        $reconstructedPayload = $extractor->reconstructPayload($apiImportRecord);

        $chunks = ApiImportPayloadChunk::query()
            ->where('api_import_record_id', $apiImportRecord->id)
            ->orderBy('chunk_index')
            ->limit($maxChunks)
            ->get(['chunk_index', 'payload_chunk', 'items_count', 'bytes_size']);

        $chunksForView = $chunks->map(function (ApiImportPayloadChunk $chunk) use ($extractor, $maxChars): array {
            $decoded = $extractor->decodeChunkPayload($chunk);

            return [
                'chunk_index' => (int) $chunk->chunk_index,
                'items_count' => $chunk->items_count,
                'bytes_size' => $chunk->bytes_size,
                'payload_pretty' => $this->prettyPrintForView($decoded, $maxChars),
            ];
        })->all();

        $maxLinked = $this->clampMaxChunks($request->query('max_linked'), 10, 100);
        $externalKeyCandidates = $this->extractExternalKeyCandidates($reconstructedPayload);
        $externalKeyCandidatesSample = array_slice($externalKeyCandidates, 0, 20);

        $linkedRecords = $this->loadLinkedRecordsByExternalKeys(
            $apiImportRecord,
            $externalKeyCandidates,
            $extractor,
            $maxChars,
            $maxLinked
        );

        $recordsForExternalKey = collect();
        $endpointOverview = collect();

        $requestedExternalKey = is_string($request->route('externalKey'))
            ? (string) $request->route('externalKey')
            : null;
        $scopeHash = is_string($apiImportRecord->sync_scope_hash) && $apiImportRecord->sync_scope_hash !== ''
            ? $apiImportRecord->sync_scope_hash
            : (is_string($requestedExternalKey) && $requestedExternalKey !== ''
                ? hash('sha256', $requestedExternalKey)
                : null);

        $articleGroupId = is_string($requestedExternalKey) && $requestedExternalKey !== ''
            ? $requestedExternalKey
            : (is_string($apiImportRecord->external_key) ? $apiImportRecord->external_key : null);

        if (is_string($scopeHash) && $scopeHash !== '') {
            $records = ApiImportRecord::query()
                ->where('sync_scope_hash', $scopeHash)
                ->with('apiEndpoint')
                ->orderBy('id')
                ->get();

            $endpointIds = $records
                ->pluck('api_endpoint_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $latestLogByEndpoint = ApiLog::query()
                ->where('api_connection_id', $apiImportRecord->api_connection_id)
                ->whereIn('endpoint_id', $endpointIds)
                ->orderByDesc('id')
                ->get(['id', 'endpoint_id', 'status_code', 'error_message', 'created_at'])
                ->unique('endpoint_id')
                ->keyBy('endpoint_id');

            $recordsForExternalKey = $records->map(function (ApiImportRecord $record) use ($extractor, $maxChars): array {
                $endpoint = $record->apiEndpoint;

                return [
                    'id' => $record->id,
                    'status' => $record->status,
                    'error_message' => $record->error_message,
                    'endpoint_id' => $record->api_endpoint_id,
                    'endpoint_name' => $endpoint?->name,
                    'endpoint_path' => $endpoint?->path,
                    'endpoint_method' => $endpoint?->method,
                    'route_by_external_key' => is_string($record->external_key) && $record->external_key !== ''
                        ? route('connect.import-records.show', ['externalKey' => $record->external_key])
                        : null,
                    'payload_pretty' => $this->prettyPrintForView($extractor->reconstructPayload($record), $maxChars),
                    'binary_preview' => $this->buildBinaryPreview($extractor->reconstructPayload($record)),
                ];
            });

            $endpointOverview = $records->groupBy('api_endpoint_id')->map(
                function (Collection $endpointRecords, int|string $endpointId) use ($latestLogByEndpoint, $extractor, $maxChars): array {
                    /** @var ApiImportRecord $first */
                    $first = $endpointRecords->first();
                    $latestLog = $latestLogByEndpoint->get((int) $endpointId);
                    $statusCode = is_object($latestLog) ? (string) ($latestLog->status_code ?? '') : '';
                    $isSuccess = str_starts_with($statusCode, '2');

                    return [
                        'endpoint_id' => (int) $endpointId,
                        'endpoint_name' => $first?->apiEndpoint?->name,
                        'endpoint_path' => $first?->apiEndpoint?->path,
                        'endpoint_method' => $first?->apiEndpoint?->method,
                        'latest_log_status_code' => $statusCode !== '' ? $statusCode : null,
                        'latest_log_error' => is_object($latestLog) ? $latestLog->error_message : null,
                        'latest_log_created_at' => is_object($latestLog) ? $latestLog->created_at?->toDateTimeString() : null,
                        'latest_log_ok' => $statusCode !== '' ? $isSuccess : null,
                        'records' => $endpointRecords->map(function (ApiImportRecord $record) use ($extractor, $maxChars): array {
                            $payload = $extractor->reconstructPayload($record);

                            return [
                                'id' => $record->id,
                                'status' => $record->status,
                                'error_message' => $record->error_message,
                                'created_at' => $record->created_at?->toDateTimeString(),
                                'route_by_external_key' => is_string($record->external_key) && $record->external_key !== ''
                                    ? route('connect.import-records.show', ['externalKey' => $record->external_key])
                                    : null,
                                'payload_pretty' => $this->prettyPrintForView($payload, $maxChars),
                                'binary_preview' => $this->buildBinaryPreview($payload),
                            ];
                        })->values()->all(),
                    ];
                }
            )->values();
        }

        return view('connect::api-import-record', [
            'apiImportRecord' => $apiImportRecord,
            'recordPayloadPretty' => $this->prettyPrintForView($recordPayload, $maxChars),
            'recordPayloadChunked' => $recordPayloadChunked,
            'recordPayloadStrategy' => $recordPayloadStrategy,
            'reconstructedPayloadPretty' => $this->prettyPrintForView($reconstructedPayload, $maxChars),
            'chunksForView' => $chunksForView,
            'truncatedChunks' => $chunksForView !== [] && count($chunksForView) < $chunksCountAll,
            'maxChars' => $maxChars,
            'linkedRecordsForView' => $linkedRecords,
            'chunksCountAll' => $chunksCountAll,
            'externalKeyCandidatesCount' => count($externalKeyCandidates),
            'externalKeyCandidatesSample' => $externalKeyCandidatesSample,
            'recordsForExternalKey' => $recordsForExternalKey,
            'endpointOverview' => $endpointOverview,
            'articleGroupId' => $articleGroupId,
            'recordBinaryPreview' => $this->buildBinaryPreview($reconstructedPayload),
        ]);
    }

    /**
     * Extract "external key" values from arbitrary payload data.
     *
     * Heuristics (generic): keys containing "external" + "key"/"id" (case-insensitive)
     * and common key spellings like external_id/externalId/externalKey.
     *
     * @return array<int, string>
     */
    private function extractExternalKeyCandidates(mixed $payload): array
    {
        $values = [];
        $nodesRemaining = 2000;

        $normalizeKey = static function (?string $key): string {
            if ($key === null) {
                return '';
            }

            return strtolower($key);
        };

        $shouldCollectKey = static function (string $normalizedKey): bool {
            if ($normalizedKey === '') {
                return false;
            }

            // external_key / externalId / externalID / ext_id / external_id, etc.
            return (bool) preg_match('/(external|ext).*?(key|id)$/i', $normalizedKey);
        };

        $collectScalar = static function (mixed $scalarValue) use (&$values): void {
            if (is_string($scalarValue) || is_int($scalarValue) || is_float($scalarValue) || is_bool($scalarValue)) {
                $scalarValue = is_bool($scalarValue) ? ($scalarValue ? 'true' : 'false') : $scalarValue;

                $s = (string) $scalarValue;
                if (trim($s) !== '') {
                    $values[$s] = $s;
                }
            }
        };

        $walk = function (mixed $node, ?string $key, int $depth) use (
            &$walk,
            $normalizeKey,
            $shouldCollectKey,
            $collectScalar,
            &$nodesRemaining,
            &$values
        ): void {
            if ($nodesRemaining-- <= 0) {
                return;
            }

            if ($depth > 10) {
                return;
            }

            if (is_array($node)) {
                foreach ($node as $k => $v) {
                    $walk($v, is_string($k) ? $k : null, $depth + 1);
                }

                return;
            }

            if (is_object($node)) {
                foreach (get_object_vars($node) as $k => $v) {
                    $walk($v, is_string($k) ? $k : null, $depth + 1);
                }

                return;
            }

            $normalizedKey = $normalizeKey($key);

            if ($shouldCollectKey($normalizedKey)) {
                $collectScalar($node);
            }

            // Additionally collect values if they look like keys we care about.
            if ($key !== null) {
                $keyLower = strtolower($key);
                if (in_array($keyLower, ['external_key', 'externalkey', 'externalid', 'external_id', 'externalkeyfield'], true)) {
                    $collectScalar($node);
                }
            }
        };

        $walk($payload, null, 0);

        return array_values($values);
    }

    /**
     * @return array<int, array{
     *   id: int,
     *   api_endpoint_id: int,
     *   external_key: string|null,
     *   status: string|null,
     *   created_at: string|null,
     *   reconstructed_payload_pretty: string
     * }>
     */
    private function loadLinkedRecordsByExternalKeys(
        ApiImportRecord $apiImportRecord,
        array $externalKeyCandidates,
        ApiImportPayloadExtractor $extractor,
        int $maxChars,
        int $maxLinked
    ): array {
        $externalKeyCandidates = array_values(array_unique(array_filter($externalKeyCandidates)));

        if ($externalKeyCandidates === []) {
            return [];
        }

        $baseQuery = ApiImportRecord::query()
            ->where('api_connection_id', $apiImportRecord->api_connection_id)
            ->whereIn('external_key', $externalKeyCandidates)
            ->where('id', '!=', $apiImportRecord->id)
            ->orderByDesc('id')
            ->limit($maxLinked);

        /** @var Collection<int, ApiImportRecord> $linkedRecords */
        $linkedRecords = $baseQuery->get(['id', 'api_endpoint_id', 'external_key', 'status', 'created_at', 'payload']);

        return $linkedRecords->map(function (ApiImportRecord $record) use ($extractor, $maxChars): array {
            $reconstructed = $extractor->reconstructPayload($record);

            return [
                'id' => (int) $record->id,
                'api_endpoint_id' => (int) $record->api_endpoint_id,
                'external_key' => $record->external_key,
                'status' => $record->status,
                'created_at' => $record->created_at?->toDateTimeString(),
                'reconstructed_payload_pretty' => $this->prettyPrintForView($reconstructed, $maxChars),
                'binary_preview' => $this->buildBinaryPreview($reconstructed),
            ];
        })->all();
    }

    /**
     * @return array{
     *   is_image: bool,
     *   is_pdf: bool,
     *   data_url: string|null,
     *   file_name: string|null
     * }
     */
    private function buildBinaryPreview(mixed $payload): array
    {
        $fileName = null;
        $rawBase64 = null;

        if (is_array($payload)) {
            $fileName = $payload['AttachmentFileName']
                ?? $payload['FileName']
                ?? $payload['filename']
                ?? null;

            $body = $payload['body'] ?? null;
            if (is_string($body)) {
                $trimmed = trim($body);
                if ($trimmed !== '' && preg_match('/^[A-Za-z0-9+\/=\r\n]+$/', $trimmed) === 1 && strlen($trimmed) > 40) {
                    $rawBase64 = $trimmed;
                }
            }

            if ($rawBase64 === null && isset($payload['Base64EncodedData']) && is_string($payload['Base64EncodedData'])) {
                $candidate = trim($payload['Base64EncodedData']);
                if ($candidate !== '') {
                    $rawBase64 = $candidate;
                }
            }
        }

        if (! is_string($rawBase64) || $rawBase64 === '') {
            return [
                'is_image' => false,
                'is_pdf' => false,
                'data_url' => null,
                'file_name' => is_string($fileName) ? $fileName : null,
            ];
        }

        $extension = is_string($fileName) ? strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION)) : null;
        $isPdf = $extension === 'pdf';

        if ($isPdf) {
            return [
                'is_image' => false,
                'is_pdf' => true,
                'data_url' => 'data:application/pdf;base64,'.$rawBase64,
                'file_name' => $fileName,
            ];
        }

        $mime = 'image/*';
        if ($extension === 'png') {
            $mime = 'image/png';
        } elseif (in_array($extension, ['jpg', 'jpeg'], true)) {
            $mime = 'image/jpeg';
        } elseif ($extension === 'gif') {
            $mime = 'image/gif';
        } elseif ($extension === 'webp') {
            $mime = 'image/webp';
        } elseif ($extension === 'svg') {
            $mime = 'image/svg+xml';
        }

        return [
            'is_image' => true,
            'is_pdf' => false,
            'data_url' => 'data:'.$mime.';base64,'.$rawBase64,
            'file_name' => $fileName,
        ];
    }

    private function clampMaxChars(mixed $value, int $default, int $max): int
    {
        if (! is_numeric($value)) {
            return $default;
        }

        $int = (int) $value;

        if ($int <= 0) {
            return $default;
        }

        return min($int, $max);
    }

    private function clampMaxChunks(mixed $value, int $default, int $max): int
    {
        if (! is_numeric($value)) {
            return $default;
        }

        $int = (int) $value;

        if ($int <= 0) {
            return $default;
        }

        return min($int, $max);
    }

    private function prettyPrintForView(mixed $value, int $maxChars): string
    {
        if (is_array($value)) {
            $encoded = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '[]';
        } elseif (is_object($value)) {
            $encoded = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
        } elseif (is_string($value)) {
            $encoded = $value;
        } else {
            $encoded = json_encode($value, JSON_UNESCAPED_SLASHES) ?: (string) $value;
        }

        $encoded = (string) $encoded;
        $encoded = trim($encoded);

        if (mb_strlen($encoded) > $maxChars) {
            $encoded = mb_substr($encoded, 0, $maxChars).'…';
        }

        return $encoded;
    }
}
