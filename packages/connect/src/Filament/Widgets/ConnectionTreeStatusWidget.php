<?php

declare(strict_types=1);

namespace Moox\Connect\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Moox\Connect\Models\ApiConnection;
use Moox\Connect\Models\ApiEndpoint;
use Moox\Connect\Models\ApiImportRecord;
use Moox\Connect\Support\QueueJobStatsService;

final class ConnectionTreeStatusWidget extends Widget
{
    private const int STALE_HASH_PREVIEW_LIMIT = 200;

    protected string $view = 'connect::connection-tree-status';

    protected int|string|array $columnSpan = 'full';

    public ?int $connectionId = null;

    /**
     * @var array<int, array<int, array<string, mixed>>>
     */
    public array $levels = [];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $currentJob = null;

    public int $queuedJobsCount = 0;

    public string $queueDriver = 'unknown';

    public bool $queuedJobsByTypeSampled = false;

    public int $queuedJobsByTypeSampleSize = 0;

    /**
     * @var array<int, array{name: string, count: int}>
     */
    public array $queuedJobsByType = [];

    /**
     * @var array<int, array{id: int, name: string}>
     */
    public array $availableConnections = [];

    public function mount(): void
    {
        $this->availableConnections = ApiConnection::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ApiConnection $c) => [
                'id' => $c->id,
                'name' => (string) $c->name,
            ])
            ->all();

        if ($this->connectionId === null) {
            $this->connectionId = $this->availableConnections[0]['id'] ?? null;
        }

        $this->refreshData();
    }

    public function updatedConnectionId(): void
    {
        $this->refreshData();
    }

    public function refreshData(): void
    {
        $this->levels = [];
        $this->currentJob = null;
        $this->queuedJobsCount = 0;
        $this->queuedJobsByType = [];

        if (! $this->connectionId) {
            return;
        }

        /** @var Collection<int, ApiEndpoint> $endpoints */
        $endpoints = ApiEndpoint::query()
            ->where('api_connection_id', $this->connectionId)
            ->orderBy('parent_endpoint_id')
            ->orderBy('id')
            ->get(['id', 'name', 'method', 'path', 'parent_endpoint_id', 'destination_model', 'field_mappings', 'key_fields'])
            ->keyBy('id');

        if ($endpoints->isEmpty()) {
            return;
        }

        $depths = [];
        $roots = [];

        foreach ($endpoints as $endpoint) {
            /** @var ApiEndpoint $endpoint */
            $parentId = $endpoint->parent_endpoint_id ? (int) $endpoint->parent_endpoint_id : null;
            if ($parentId === null || ! $endpoints->has($parentId)) {
                $depths[$endpoint->id] = 0;
                $roots[] = $endpoint->id;
            }
        }

        $queue = $roots;
        while ($queue !== []) {
            $current = array_shift($queue);
            foreach ($endpoints as $endpoint) {
                /** @var ApiEndpoint $endpoint */
                if ((int) $endpoint->parent_endpoint_id === (int) $current && ! array_key_exists($endpoint->id, $depths)) {
                    $depths[$endpoint->id] = ($depths[$current] ?? 0) + 1;
                    $queue[] = $endpoint->id;
                }
            }
        }

        $endpointStatus = $this->buildEndpointStatus();
        $recordCounts = ApiImportRecord::query()
            ->where('api_connection_id', $this->connectionId)
            ->selectRaw('api_endpoint_id, count(*) as c')
            ->groupBy('api_endpoint_id')
            ->pluck('c', 'api_endpoint_id')
            ->all();
        $processedCounts = ApiImportRecord::query()
            ->where('api_connection_id', $this->connectionId)
            ->whereIn('status', ['processed', 'synced'])
            ->selectRaw('api_endpoint_id, count(*) as c')
            ->groupBy('api_endpoint_id')
            ->pluck('c', 'api_endpoint_id')
            ->all();
        $failedCounts = ApiImportRecord::query()
            ->where('api_connection_id', $this->connectionId)
            ->where('status', 'failed')
            ->selectRaw('api_endpoint_id, count(*) as c')
            ->groupBy('api_endpoint_id')
            ->pluck('c', 'api_endpoint_id')
            ->all();
        $recordsLast24h = ApiImportRecord::query()
            ->where('api_connection_id', $this->connectionId)
            ->where('created_at', '>=', now()->subDay())
            ->selectRaw('api_endpoint_id, count(*) as c')
            ->groupBy('api_endpoint_id')
            ->pluck('c', 'api_endpoint_id')
            ->all();
        $lastUpdatedByEndpoint = ApiImportRecord::query()
            ->where('api_connection_id', $this->connectionId)
            ->selectRaw('api_endpoint_id, max(updated_at) as last_updated_at')
            ->groupBy('api_endpoint_id')
            ->pluck('last_updated_at', 'api_endpoint_id')
            ->all();
        $statusSplit = $this->buildStatusSplitByEndpoint();
        $failedReasons = $this->buildFailedReasonTopByEndpoint();

        foreach ($depths as $endpointId => $depth) {
            $endpoint = $endpoints->get($endpointId);
            if (! $endpoint) {
                continue;
            }
            $hashHealth = $this->buildHashHealthForEndpoint($endpointId);

            $status = $endpointStatus[$endpointId] ?? [
                'status' => 'pending',
                'status_label' => 'Noch nie gelaufen',
                'color' => 'gray',
            ];

            $this->levels[$depth][] = [
                'endpoint_id' => $endpointId,
                'parent_endpoint_id' => $endpoint->parent_endpoint_id ? (int) $endpoint->parent_endpoint_id : null,
                'name' => (string) $endpoint->name,
                'method' => (string) $endpoint->method,
                'path' => (string) $endpoint->path,
                'status' => $status['status'],
                'status_label' => $status['status_label'],
                'color' => $status['color'],
                'record_count' => (int) ($recordCounts[$endpointId] ?? 0),
                'processed_count' => (int) ($processedCounts[$endpointId] ?? 0),
                'failed_count' => (int) ($failedCounts[$endpointId] ?? 0),
                'records_last_24h' => (int) ($recordsLast24h[$endpointId] ?? 0),
                'last_updated_at' => $this->formatDateTime($lastUpdatedByEndpoint[$endpointId] ?? null),
                'destination_model' => $endpoint->destination_model,
                'field_mappings' => $this->normalizeMappings($endpoint->field_mappings),
                'key_fields' => $this->normalizeKeyValueMap($endpoint->key_fields),
                'hash_total' => $hashHealth['total'],
                'hash_current' => $hashHealth['current'],
                'hash_stale' => $hashHealth['stale'],
                'latest_hashes' => $hashHealth['latest'],
                'stale_hashes_preview' => $this->getStaleHashesPreviewForEndpoint($endpointId),
                'status_split' => $statusSplit[$endpointId] ?? [
                    'pending' => 0,
                    'processing' => 0,
                    'failed' => 0,
                    'processed' => 0,
                    'synced' => 0,
                ],
                'failed_reason_top' => $failedReasons[$endpointId] ?? [],
            ];
        }

        ksort($this->levels);

        $this->currentJob = $this->findCurrentJob();
        $this->loadQueueStats();
    }

    /**
     * @return array<int, array{status: string, status_label: string, color: string}>
     */
    private function buildEndpointStatus(): array
    {
        $status = [];

        $records = ApiImportRecord::query()
            ->where('api_connection_id', $this->connectionId)
            ->orderByDesc('id')
            ->limit(500)
            ->get(['api_endpoint_id', 'status', 'created_at']);

        foreach ($records as $record) {
            /** @var ApiImportRecord $record */
            $endpointId = (int) $record->api_endpoint_id;
            if (! $endpointId) {
                continue;
            }

            $state = (string) ($record->status ?? '');

            if ($state === 'failed') {
                $status[$endpointId] = [
                    'status' => 'failed',
                    'status_label' => 'Fehler (zuletzt '.$record->created_at?->diffForHumans().')',
                    'color' => 'danger',
                ];

                continue;
            }

            if ($state === 'processed' || $state === 'synced') {
                $status[$endpointId] = [
                    'status' => 'done',
                    'status_label' => 'Zuletzt gelaufen '.$record->created_at?->diffForHumans(),
                    'color' => 'success',
                ];
            }
        }

        return $status;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findCurrentJob(): ?array
    {
        $record = ApiImportRecord::query()
            ->where('api_connection_id', $this->connectionId)
            ->orderByDesc('id')
            ->first();

        if (! $record) {
            return null;
        }

        return [
            'event' => $record->status,
            'job' => null,
            'endpoint_id' => $record->api_endpoint_id,
            'status_code' => null,
            'created_at' => $record->created_at?->toDateTimeString(),
        ];
    }

    private function loadQueueStats(): void
    {
        $stats = app(QueueJobStatsService::class)->summarizeQueuedJobsByType();

        $this->queueDriver = (string) ($stats['queue_driver'] ?? 'unknown');
        $this->queuedJobsCount = (int) ($stats['total'] ?? 0);
        $this->queuedJobsByType = is_array($stats['by_type'] ?? null) ? $stats['by_type'] : [];
        $this->queuedJobsByTypeSampled = (bool) ($stats['sampled'] ?? false);
        $this->queuedJobsByTypeSampleSize = (int) ($stats['sample_size'] ?? 0);
    }

    /**
     * @return array<int, array{pending: int, processing: int, failed: int, processed: int, synced: int}>
     */
    private function buildStatusSplitByEndpoint(): array
    {
        $rows = ApiImportRecord::query()
            ->where('api_connection_id', $this->connectionId)
            ->selectRaw('api_endpoint_id, status, count(*) as c')
            ->groupBy('api_endpoint_id', 'status')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $endpointId = (int) $row->api_endpoint_id;
            $status = (string) ($row->status ?? '');
            if ($endpointId <= 0 || ! in_array($status, ['pending', 'processing', 'failed', 'processed', 'synced'], true)) {
                continue;
            }

            if (! isset($result[$endpointId])) {
                $result[$endpointId] = [
                    'pending' => 0,
                    'processing' => 0,
                    'failed' => 0,
                    'processed' => 0,
                    'synced' => 0,
                ];
            }

            $result[$endpointId][$status] = (int) $row->c;
        }

        return $result;
    }

    /**
     * @return array<int, array<int, array{message: string, count: int}>>
     */
    private function buildFailedReasonTopByEndpoint(): array
    {
        $rows = ApiImportRecord::query()
            ->where('api_connection_id', $this->connectionId)
            ->where('status', 'failed')
            ->selectRaw('api_endpoint_id, coalesce(nullif(error_message, ""), "(ohne Nachricht)") as msg, count(*) as c')
            ->groupBy('api_endpoint_id', 'msg')
            ->orderByDesc('c')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $endpointId = (int) $row->api_endpoint_id;
            if ($endpointId <= 0) {
                continue;
            }

            $result[$endpointId] ??= [];
            if (count($result[$endpointId]) >= 5) {
                continue;
            }

            $result[$endpointId][] = [
                'message' => (string) $row->msg,
                'count' => (int) $row->c,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array{external_field: string, internal_field: string}>
     */
    private function normalizeMappings(mixed $mappings): array
    {
        if (! is_array($mappings)) {
            return [];
        }

        $result = [];
        foreach ($mappings as $mapping) {
            if (! is_array($mapping)) {
                continue;
            }

            $externalField = trim((string) ($mapping['external_field'] ?? ''));
            $internalField = trim((string) ($mapping['internal_field'] ?? ''));
            if ($externalField === '' || $internalField === '') {
                continue;
            }

            $result[] = [
                'external_field' => $externalField,
                'internal_field' => $internalField,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array{internal: string, external: string}>
     */
    private function normalizeKeyValueMap(mixed $map): array
    {
        if (! is_array($map)) {
            return [];
        }

        $result = [];
        foreach ($map as $internal => $external) {
            $internalKey = trim((string) $internal);
            $externalKey = trim((string) $external);
            if ($internalKey === '' || $externalKey === '') {
                continue;
            }

            $result[] = [
                'internal' => $internalKey,
                'external' => $externalKey,
            ];
        }

        return $result;
    }

    private function formatDateTime(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{total: int, current: int, stale: int, latest: array<int, array{hash: string, status: string, updated_at: string|null}>}
     */
    private function buildHashHealthForEndpoint(int $endpointId): array
    {
        $latestRecordIdsByHash = ApiImportRecord::query()
            ->where('api_connection_id', $this->connectionId)
            ->where('api_endpoint_id', $endpointId)
            ->whereNotNull('sync_scope_hash')
            ->where('sync_scope_hash', '!=', '')
            ->selectRaw('sync_scope_hash, max(id) as latest_id')
            ->groupBy('sync_scope_hash')
            ->pluck('latest_id')
            ->all();

        if ($latestRecordIdsByHash === []) {
            return [
                'total' => 0,
                'current' => 0,
                'stale' => 0,
                'latest' => [],
            ];
        }

        $latestRecords = ApiImportRecord::query()
            ->whereIn('id', $latestRecordIdsByHash)
            ->get(['sync_scope_hash', 'status', 'updated_at'])
            ->sortByDesc('updated_at')
            ->values();

        $current = 0;
        $stale = 0;
        $latest = [];

        foreach ($latestRecords as $index => $record) {
            $isCurrent = in_array((string) $record->status, ['processed', 'synced'], true);
            if ($isCurrent) {
                $current++;
            } else {
                $stale++;
            }

            if ($index < 5) {
                $latest[] = [
                    'hash' => (string) $record->sync_scope_hash,
                    'status' => (string) $record->status,
                    'updated_at' => $record->updated_at?->toDateTimeString(),
                ];
            }
        }

        return [
            'total' => $latestRecords->count(),
            'current' => $current,
            'stale' => $stale,
            'latest' => $latest,
        ];
    }

    /**
     * Neueste Stale-Hashes pro Scope (begrenzt für die UI).
     *
     * @return array<int, array{hash: string, status: string, updated_at: string|null}>
     */
    private function getStaleHashesPreviewForEndpoint(int $endpointId): array
    {
        $latestRecordIdsByHash = ApiImportRecord::query()
            ->where('api_connection_id', $this->connectionId)
            ->where('api_endpoint_id', $endpointId)
            ->whereNotNull('sync_scope_hash')
            ->where('sync_scope_hash', '!=', '')
            ->selectRaw('sync_scope_hash, max(id) as latest_id')
            ->groupBy('sync_scope_hash')
            ->pluck('latest_id')
            ->all();

        if ($latestRecordIdsByHash === []) {
            return [];
        }

        return ApiImportRecord::query()
            ->whereIn('id', $latestRecordIdsByHash)
            ->whereNotIn('status', ['processed', 'synced'])
            ->orderByDesc('updated_at')
            ->limit(self::STALE_HASH_PREVIEW_LIMIT)
            ->get(['sync_scope_hash', 'status', 'updated_at'])
            ->map(fn (ApiImportRecord $record) => [
                'hash' => (string) $record->sync_scope_hash,
                'status' => (string) $record->status,
                'updated_at' => $record->updated_at?->toDateTimeString(),
            ])
            ->all();
    }
}
