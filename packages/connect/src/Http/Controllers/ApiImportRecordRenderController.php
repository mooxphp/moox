<?php

declare(strict_types=1);

namespace Moox\Connect\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
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
    private const RECENT_RECORDS_PER_ENDPOINT = 50;

    private const EXTERNAL_ROUTE_COUNTS_LIMIT = 20;

    public function showParentEndpoint(Request $request, int $parentEndpointId): View
    {
        $parentEndpoint = ApiEndpoint::query()->findOrFail($parentEndpointId);
        $endpointIds = $this->collectEndpointTreeIds((int) $parentEndpoint->id);

        $parentKey = $request->query('parent_key');
        $scopeHash = is_string($parentKey) && $parentKey !== ''
            ? hash('sha256', $parentKey)
            : null;

        if (! is_string($parentKey) || $parentKey === '') {
            $parentKey = null;
        }

        $endpointIdsWithRecords = $this->endpointIdsWithRecords($endpointIds, $scopeHash);
        $apiConnectionId = $this->resolveApiConnectionId($endpointIds, $scopeHash);
        $recordTotalsByEndpoint = $this->loadRecordTotalsByEndpoint($endpointIds, $scopeHash);
        $externalRouteCountsByEndpoint = $this->loadExternalRouteCountsByEndpoint(
            $endpointIdsWithRecords,
            $scopeHash,
        );
        $recentRecords = $this->loadRecentRecordsPerEndpoint($endpointIdsWithRecords, $scopeHash);
        $overview = $this->buildOverviewFromRecords(
            $recentRecords,
            $externalRouteCountsByEndpoint,
            $recordTotalsByEndpoint,
            $scopeHash,
            $apiConnectionId,
        );
        $stats = $this->buildOverviewStatsFromQuery(
            $endpointIdsWithRecords,
            $scopeHash,
            $apiConnectionId,
            $recordTotalsByEndpoint,
        );

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

    /**
     * @param  array<int, array<int, array{
     *   external_key: string|null,
     *   route_method: string|null,
     *   route_path: string|null,
     *   count: int
     * }>>  $externalRouteCountsByEndpoint
     * @param  array<int, int>  $recordTotalsByEndpoint
     */
    private function buildOverviewFromRecords(
        Collection $records,
        array $externalRouteCountsByEndpoint,
        array $recordTotalsByEndpoint,
        ?string $scopeHash,
        ?int $apiConnectionId,
    ): Collection {
        $endpointIds = $records->pluck('api_endpoint_id')->filter()->unique()->values()->all();
        $queueStats = app(QueueJobStatsService::class)->summarizeRunEndpointForItemJobsByEndpoint();
        $latestLogByEndpoint = $this->loadLatestLogsByEndpoint($endpointIds, $apiConnectionId);
        $endpoints = ApiEndpoint::query()
            ->whereIn('id', $endpointIds)
            ->get(['id', 'name', 'method', 'path'])
            ->keyBy('id');

        return $records->groupBy('api_endpoint_id')->map(
            function (Collection $endpointRecords, int|string $endpointId) use (
                $latestLogByEndpoint,
                $queueStats,
                $externalRouteCountsByEndpoint,
                $recordTotalsByEndpoint,
                $scopeHash,
                $endpoints,
            ): array {
                $endpointId = (int) $endpointId;
                /** @var ApiImportRecord|null $first */
                $first = $endpointRecords->first();
                $endpoint = $endpoints->get($endpointId);
                $latestLog = $latestLogByEndpoint->get($endpointId);
                $statusCode = is_object($latestLog) ? (string) ($latestLog->status_code ?? '') : '';
                $routeMethod = $endpoint?->method ?? $first?->apiEndpoint?->method;
                $routePath = $endpoint?->path ?? $first?->apiEndpoint?->path;
                $queuedJobsForRoute = (int) ($queueStats['by_endpoint'][$endpointId] ?? 0);
                $recordsTotal = (int) ($recordTotalsByEndpoint[$endpointId] ?? $endpointRecords->count());
                $recordsShown = $endpointRecords->count();
                /** @var ApiImportRecord|null $latestFailedRecord */
                $latestFailedRecord = $endpointRecords
                    ->filter(function (ApiImportRecord $record): bool {
                        return strtolower((string) ($record->status ?? '')) === 'failed';
                    })
                    ->sortByDesc('id')
                    ->first();

                if ($latestFailedRecord === null && $recordsTotal > $recordsShown) {
                    $latestFailedRecord = ApiImportRecord::query()
                        ->where('api_endpoint_id', $endpointId)
                        ->when(
                            $scopeHash !== null,
                            fn (Builder $query): Builder => $query->where('sync_scope_hash', $scopeHash),
                        )
                        ->where('status', 'failed')
                        ->orderByDesc('id')
                        ->first(['id', 'external_key']);
                }

                $externalRouteCounts = $externalRouteCountsByEndpoint[$endpointId] ?? [];
                if ($externalRouteCounts === []) {
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
                }

                return [
                    'endpoint_id' => $endpointId,
                    'endpoint_name' => $endpoint?->name ?? $first?->apiEndpoint?->name,
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
                    'records_total' => $recordsTotal,
                    'records_truncated' => $recordsTotal > $recordsShown,
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
     * @param  array<int, int>  $endpointIds
     * @param  array<int, int>  $recordTotalsByEndpoint
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
    private function buildOverviewStatsFromQuery(
        array $endpointIds,
        ?string $scopeHash,
        ?int $apiConnectionId,
        array $recordTotalsByEndpoint,
    ): array {
        $queueStats = app(QueueJobStatsService::class)->summarizeRunEndpointForItemJobsByEndpoint();
        $byStatus = $this->scopedRecordsQuery($endpointIds, $scopeHash)
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $recordsFailed = (int) ($byStatus['failed'] ?? 0);
        $recordsProcessed = (int) ($byStatus['processed'] ?? 0);
        $recordsNew = (int) ($byStatus['new'] ?? 0)
            + (int) ($byStatus['fetched'] ?? 0)
            + (int) ($byStatus['update'] ?? 0);
        $recordsTotal = (int) $byStatus->sum();

        $latestLogByEndpoint = $this->loadLatestLogsByEndpoint($endpointIds, $apiConnectionId);
        $endpointsTotal = count($recordTotalsByEndpoint);
        $endpointsOk = 0;
        $endpointsError = 0;
        $endpointsUnknown = 0;

        foreach (array_keys($recordTotalsByEndpoint) as $endpointId) {
            $latestLog = $latestLogByEndpoint->get((int) $endpointId);
            $statusCode = is_object($latestLog) ? (string) ($latestLog->status_code ?? '') : '';

            if ($statusCode === '') {
                $endpointsUnknown++;
            } elseif (str_starts_with($statusCode, '2')) {
                $endpointsOk++;
            } else {
                $endpointsError++;
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
     * @param  array<int, int>  $endpointIds
     */
    private function scopedRecordsQuery(array $endpointIds, ?string $scopeHash): Builder
    {
        $query = ApiImportRecord::query();

        if ($scopeHash !== null) {
            return $query->where('sync_scope_hash', $scopeHash);
        }

        return $query->whereIn('api_endpoint_id', $endpointIds);
    }

    /**
     * @param  array<int, int>  $endpointIds
     * @return array<int, int>
     */
    private function endpointIdsWithRecords(array $endpointIds, ?string $scopeHash): array
    {
        return $this->scopedRecordsQuery($endpointIds, $scopeHash)
            ->distinct()
            ->orderBy('api_endpoint_id')
            ->pluck('api_endpoint_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $endpointIds
     */
    private function resolveApiConnectionId(array $endpointIds, ?string $scopeHash): ?int
    {
        $apiConnectionId = $this->scopedRecordsQuery($endpointIds, $scopeHash)
            ->value('api_connection_id');

        return is_numeric($apiConnectionId) ? (int) $apiConnectionId : null;
    }

    /**
     * @param  array<int, int>  $endpointIds
     * @return Collection<int, ApiLog>
     */
    private function loadLatestLogsByEndpoint(array $endpointIds, ?int $apiConnectionId): Collection
    {
        if ($apiConnectionId === null || $endpointIds === []) {
            return collect();
        }

        $latestLogIds = ApiLog::query()
            ->where('api_connection_id', $apiConnectionId)
            ->whereIn('endpoint_id', $endpointIds)
            ->selectRaw('MAX(id) as id')
            ->groupBy('endpoint_id')
            ->pluck('id');

        if ($latestLogIds->isEmpty()) {
            return collect();
        }

        return ApiLog::query()
            ->whereIn('id', $latestLogIds)
            ->get(['id', 'endpoint_id', 'status_code', 'error_message', 'created_at'])
            ->keyBy('endpoint_id');
    }

    /**
     * @param  array<int, int>  $endpointIds
     * @return array<int, int>
     */
    private function loadRecordTotalsByEndpoint(array $endpointIds, ?string $scopeHash): array
    {
        return $this->scopedRecordsQuery($endpointIds, $scopeHash)
            ->selectRaw('api_endpoint_id, count(*) as c')
            ->groupBy('api_endpoint_id')
            ->pluck('c', 'api_endpoint_id')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();
    }

    /**
     * @param  array<int, int>  $endpointIds
     * @return array<int, array<int, array{
     *   external_key: string|null,
     *   route_method: string|null,
     *   route_path: string|null,
     *   count: int
     * }>>
     */
    private function loadExternalRouteCountsByEndpoint(array $endpointIds, ?string $scopeHash): array
    {
        if ($endpointIds === []) {
            return [];
        }

        $endpoints = ApiEndpoint::query()
            ->whereIn('id', $endpointIds)
            ->get(['id', 'method', 'path'])
            ->keyBy('id');

        $result = [];

        foreach ($endpointIds as $endpointId) {
            $endpointId = (int) $endpointId;
            $endpoint = $endpoints->get($endpointId);
            $routeMethod = $endpoint?->method;
            $routePath = $endpoint?->path;

            $rows = ApiImportRecord::query()
                ->where('api_endpoint_id', $endpointId)
                ->when(
                    $scopeHash !== null,
                    fn (Builder $query): Builder => $query->where('sync_scope_hash', $scopeHash),
                )
                ->selectRaw('external_key, count(*) as c')
                ->groupBy('external_key')
                ->orderByDesc('c')
                ->limit(self::EXTERNAL_ROUTE_COUNTS_LIMIT)
                ->get();

            $result[$endpointId] = $rows->map(function ($row) use ($routeMethod, $routePath): array {
                $externalKey = is_string($row->external_key) && $row->external_key !== ''
                    ? $row->external_key
                    : null;

                return [
                    'external_key' => $externalKey,
                    'route_method' => $routeMethod,
                    'route_path' => $routePath,
                    'count' => (int) ($row->c ?? 0),
                ];
            })->values()->all();
        }

        return $result;
    }

    /**
     * @param  array<int, int>  $endpointIds
     * @return Collection<int, ApiImportRecord>
     */
    private function loadRecentRecordsPerEndpoint(array $endpointIds, ?string $scopeHash): Collection
    {
        $records = collect();

        foreach ($endpointIds as $endpointId) {
            $chunk = ApiImportRecord::query()
                ->with('apiEndpoint')
                ->where('api_endpoint_id', (int) $endpointId)
                ->when(
                    $scopeHash !== null,
                    fn (Builder $query): Builder => $query->where('sync_scope_hash', $scopeHash),
                )
                ->orderByDesc('id')
                ->limit(self::RECENT_RECORDS_PER_ENDPOINT)
                ->get([
                    'id',
                    'api_endpoint_id',
                    'api_connection_id',
                    'external_key',
                    'status',
                    'created_at',
                    'sync_scope_hash',
                ]);

            $records = $records->concat($chunk);
        }

        return $records;
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
        $maxChunks = $this->clampMaxChunks($request->query('max_chunks'), 100, 200);

        $recordPayload = $apiImportRecord->payload ?? [];
        $recordPayloadChunked = $recordPayload['chunked'] ?? null;
        $recordPayloadStrategy = $recordPayload['strategy'] ?? null;
        $recordPayloadTotalItems = $recordPayload['total_items'] ?? null;
        $recordPayloadPreview = $recordPayload['preview'] ?? null;

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

        $scopeExternalKey = is_string($requestedExternalKey) && $requestedExternalKey !== ''
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
            'recordPayloadTotalItems' => $recordPayloadTotalItems,
            'recordPayloadPreviewPretty' => $this->prettyPrintForView($recordPayloadPreview, $maxChars),
            'maxChunks' => $maxChunks,
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
            'scopeExternalKey' => $scopeExternalKey,
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
            foreach ((array) config('connect.binary_preview.file_name_keys', ['file_name', 'filename', 'FileName']) as $key) {
                if (! is_string($key) || $key === '') {
                    continue;
                }

                $candidate = $payload[$key] ?? null;
                if (is_string($candidate) && $candidate !== '') {
                    $fileName = $candidate;
                    break;
                }
            }

            foreach ((array) config('connect.binary_preview.base64_keys', ['body', 'base64', 'Base64EncodedData']) as $key) {
                if (! is_string($key) || $key === '' || $rawBase64 !== null) {
                    continue;
                }

                $candidate = $payload[$key] ?? null;
                if (! is_string($candidate)) {
                    continue;
                }

                $trimmed = trim($candidate);
                if ($trimmed === '') {
                    continue;
                }

                if ($key === 'body' && preg_match('/^[A-Za-z0-9+\/=\r\n]+$/', $trimmed) !== 1) {
                    continue;
                }

                $rawBase64 = $trimmed;
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
