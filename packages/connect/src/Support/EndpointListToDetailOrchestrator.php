<?php

declare(strict_types=1);

namespace Moox\Connect\Support;

use Illuminate\Support\Facades\Bus;
use Moox\Connect\Jobs\FetchImportRecordsJob;
use Moox\Connect\Jobs\FinalizeDetailSyncJob;
use Moox\Connect\Jobs\RunEndpointForItemJob;
use Moox\Connect\Models\ApiEndpoint;
use Moox\Connect\Models\ApiImportPayloadChunk;
use Moox\Connect\Models\ApiImportRecord;
use Moox\Connect\Models\ApiLog;

class EndpointListToDetailOrchestrator
{
    /**
     * Baut die Detail-Jobs (ohne zu dispatchen) und liefert zusätzlich Prune-Metadaten.
     *
     * @return array{jobs: array<int, object>, seen: array<string, array<int, string>>, meta: array<string, mixed>}
     */
    public function buildDetailJobs(ApiEndpoint $detailEndpoint, ?string $parentSyncBatchId = null, ?string $treeRunId = null, bool $throwOnFailure = false): array
    {
        $connectionId = $detailEndpoint->api_connection_id ?? null;

        if (! $detailEndpoint->parent_endpoint_id) {
            $this->log($connectionId, $detailEndpoint->id, 'orchestrator_abort', [
                'reason' => 'detail_endpoint.parent_endpoint_id is null',
            ], '0', 'parent_endpoint_id fehlt');

            return ['jobs' => [], 'seen' => [], 'meta' => ['aborted' => true]];
        }

        $parent = ApiEndpoint::find($detailEndpoint->parent_endpoint_id);
        if (! $parent) {
            $this->log($connectionId, $detailEndpoint->id, 'orchestrator_abort', [
                'reason' => 'parent endpoint not found',
                'parent_endpoint_id' => $detailEndpoint->parent_endpoint_id,
            ], '0', 'parent endpoint nicht gefunden');

            return ['jobs' => [], 'seen' => [], 'meta' => ['aborted' => true]];
        }

        $itemPath = $detailEndpoint->list_item_path ?: ($parent->list_item_path ?: null);
        $idKey = $detailEndpoint->list_id_key ?: ($parent->list_id_key ?: null);

        if (! $itemPath || ! $idKey) {
            $this->log($connectionId, $detailEndpoint->id, 'orchestrator_abort', [
                'reason' => 'list_item_path or list_id_key missing (detail + parent fallback)',
                'parent_endpoint_id' => $parent->id,
                'parent_endpoint_name' => $parent->name ?? null,
                'parent_list_item_path' => $parent->list_item_path,
                'parent_list_id_key' => $parent->list_id_key,
                'detail_list_item_path' => $detailEndpoint->list_item_path,
                'detail_list_id_key' => $detailEndpoint->list_id_key,
                'hint' => 'Setze list_item_path/list_id_key am Detail-Endpoint, oder weiterhin am Parent für Fallback.',
            ], '0', 'list_item_path/list_id_key fehlen (Detail + Parent)');

            return ['jobs' => [], 'seen' => [], 'meta' => ['aborted' => true]];
        }

        $jobs = [];
        $seenKeysByScope = [];
        $scheduledRequests = [];

        $shouldUseFetch = is_array($detailEndpoint->response_map)
            && isset($detailEndpoint->response_map['items_path'])
            && is_string($detailEndpoint->response_map['items_path'])
            && $detailEndpoint->response_map['items_path'] !== '';

        $routeParamKey = $detailEndpoint->route_param_key ?: null;
        if (! $routeParamKey) {
            preg_match_all('/\{(?<name>[^}]+)\}/', (string) $detailEndpoint->path, $m);
            $routeParamKey = $m['name'][0] ?? null;
        }

        $rateLimit = $parent->rate_limit ?? 60;
        $chunkSize = max(1, min(200, (int) floor($rateLimit / 2) ?: 50));

        $parentQuery = ApiImportRecord::where('api_endpoint_id', $parent->id)
            ->whereIn('status', ['new', 'fetched', 'update', 'processed']);

        if ($parentSyncBatchId) {
            $parentQuery->where('sync_batch_id', $parentSyncBatchId);
        }

        $parentQuery
            ->chunkById($chunkSize, function ($records) use (
                &$jobs,
                &$seenKeysByScope,
                &$scheduledRequests,
                $detailEndpoint,
                $itemPath,
                $idKey,
                $treeRunId,
                $throwOnFailure,
                $shouldUseFetch,
                $routeParamKey
            ): void {
                foreach ($records as $record) {
                    /** @var ApiImportRecord $record */
                    $payload = $this->rebuildPayload($record);

                    $wildcardSpec = $this->parseWildcardListChildPath($itemPath);
                    if ($wildcardSpec !== null) {
                        [$parentListPath, $childPath] = $wildcardSpec;

                        $parents = $parentListPath === '*'
                            ? $payload
                            : data_get($payload, $parentListPath, []);

                        if (! is_array($parents)) {
                            continue;
                        }

                        foreach ($parents as $parentItem) {
                            if (! is_array($parentItem)) {
                                continue;
                            }

                            $items = data_get($parentItem, $childPath, []);
                            if (! is_array($items)) {
                                continue;
                            }

                            foreach ($items as $item) {
                                if (! is_array($item) || ! array_key_exists($idKey, $item)) {
                                    continue;
                                }

                                $idValue = $item[$idKey];
                                if (! is_scalar($idValue)) {
                                    continue;
                                }

                                $externalKey = $this->resolveExternalKey(
                                    $detailEndpoint->external_key_field,
                                    $payload,
                                    $parentItem,
                                    $item
                                );

                                $scopeHash = $externalKey !== null && $externalKey !== ''
                                    ? hash('sha256', (string) $externalKey)
                                    : null;

                                $seenKeysByScope[$scopeHash ?? '__null'][] = (string) $idValue;

                                $requestId = (string) $idValue;
                                $externalKeyString = $externalKey !== null ? (string) $externalKey : null;
                                $dedupeKey = $requestId.'|'.($externalKeyString ?? '__null');

                                if (isset($scheduledRequests[$dedupeKey])) {
                                    continue;
                                }
                                $scheduledRequests[$dedupeKey] = true;

                                if ($shouldUseFetch) {
                                    $parameters = $routeParamKey
                                        ? [$routeParamKey => $requestId]
                                        : [];

                                    $jobs[] = new FetchImportRecordsJob($detailEndpoint->id, $parameters);
                                } else {
                                    $jobs[] = new RunEndpointForItemJob(
                                        $detailEndpoint->id,
                                        $requestId,
                                        $externalKeyString,
                                        $treeRunId,
                                        $throwOnFailure,
                                    );
                                }
                            }
                        }

                        continue;
                    }

                    $items = $itemPath === '*'
                        ? $payload
                        : data_get($payload, $itemPath, []);

                    if (! is_array($items)) {
                        continue;
                    }

                    if ($this->isArrayOfArrays($items) && $this->shouldFlattenOneLevel($items, $idKey)) {
                        $items = array_merge([], ...array_values($items));
                    }

                    foreach ($items as $item) {
                        if (! is_array($item) || ! array_key_exists($idKey, $item)) {
                            continue;
                        }

                        $idValue = $item[$idKey];
                        if (! is_scalar($idValue)) {
                            continue;
                        }

                        $externalKey = $this->resolveExternalKey(
                            $detailEndpoint->external_key_field,
                            $payload,
                            null,
                            $item
                        );

                        $scopeHash = $externalKey !== null && $externalKey !== ''
                            ? hash('sha256', (string) $externalKey)
                            : null;

                        $seenKeysByScope[$scopeHash ?? '__null'][] = (string) $idValue;

                        $requestId = (string) $idValue;
                        $externalKeyString = $externalKey !== null ? (string) $externalKey : null;
                        $dedupeKey = $requestId.'|'.($externalKeyString ?? '__null');

                        if (isset($scheduledRequests[$dedupeKey])) {
                            continue;
                        }
                        $scheduledRequests[$dedupeKey] = true;

                        if ($shouldUseFetch) {
                            $parameters = $routeParamKey
                                ? [$routeParamKey => $requestId]
                                : [];

                            $jobs[] = new FetchImportRecordsJob($detailEndpoint->id, $parameters);
                        } else {
                            $jobs[] = new RunEndpointForItemJob(
                                $detailEndpoint->id,
                                $requestId,
                                $externalKeyString,
                                $treeRunId,
                                $throwOnFailure,
                            );
                        }
                    }
                }
            });

        return [
            'jobs' => $jobs,
            'seen' => $seenKeysByScope,
            'meta' => [
                'effective_list_item_path' => $itemPath,
                'effective_list_id_key' => $idKey,
                'jobs_count' => count($jobs),
                'prune_scopes' => count($seenKeysByScope),
            ],
        ];
    }

    /**
     * Führt für alle "Listen"-ImportRecords des Parent-Endpoints die Detail-Requests aus.
     *
     * Für jeden Item im Full-Response:
     * - liest die ID über list_id_key
     * - ersetzt {route_param_key} im Pfad des Detail-Endpoints
     * - setzt optional eine Variable (variable_key) im Request
     * - ruft ApiEndpointRunner aus, um den Detail-Endpoint auszuführen
     */
    public function generateDetailImports(ApiEndpoint $detailEndpoint): int
    {
        $connectionId = $detailEndpoint->api_connection_id ?? null;

        $this->log($connectionId, $detailEndpoint->id, 'orchestrator_start', [
            'detail_endpoint_id' => $detailEndpoint->id,
            'detail_endpoint_name' => $detailEndpoint->name ?? null,
            'detail_parent_endpoint_id' => $detailEndpoint->parent_endpoint_id,
        ]);

        $syncMode = $detailEndpoint->sync_mode ?: 'append';

        $built = $this->buildDetailJobs($detailEndpoint);
        $jobs = $built['jobs'] ?? [];
        $seenKeysByScope = $built['seen'] ?? [];

        if ($jobs !== []) {
            $batch = Bus::batch($jobs)
                ->name(sprintf('connect:detail endpoint=%d', $detailEndpoint->id))
                ->dispatch();

            if ($syncMode === 'sync') {
                FinalizeDetailSyncJob::dispatch(
                    $detailEndpoint->id,
                    $batch->id,
                    $seenKeysByScope
                );
            }
        }

        $this->log($connectionId, $detailEndpoint->id, 'orchestrator_done', [
            'created_jobs' => count($jobs),
            'sync_mode' => $syncMode,
            'prune_scopes' => count($seenKeysByScope),
        ], '200');

        return count($jobs);
    }

    public function pruneMissingDetailRecords(ApiEndpoint $detailEndpoint, array $seenKeysByScope): void
    {
        foreach ($seenKeysByScope as $scopeKey => $keys) {
            $keys = array_values(array_unique($keys));
            if ($keys === []) {
                continue;
            }

            $query = ApiImportRecord::query()->where('api_endpoint_id', $detailEndpoint->id);

            if ($scopeKey === '__null') {
                $query->whereNull('sync_scope_hash');
            } else {
                $query->where('sync_scope_hash', $scopeKey);
            }

            $query->whereNotIn('external_key', $keys)->delete();
        }
    }

    protected function rebuildPayload(ApiImportRecord $record): array
    {
        $meta = $record->payload ?? [];

        if (! ($meta['chunked'] ?? false)) {
            return $meta;
        }

        $chunks = ApiImportPayloadChunk::where('api_import_record_id', $record->id)
            ->orderBy('chunk_index')
            ->pluck('payload_chunk')
            ->all();

        if (($meta['strategy'] ?? null) === 'list') {
            $items = [];
            foreach ($chunks as $json) {
                $data = json_decode($json, true) ?? [];
                $items = array_merge($items, $data);
            }

            return $items;
        }

        $json = implode('', $chunks);

        return json_decode($json, true) ?? [];
    }

    private function isArrayOfArrays(array $value): bool
    {
        foreach ($value as $v) {
            if (! is_array($v)) {
                return false;
            }
        }

        return $value !== [];
    }

    private function shouldFlattenOneLevel(array $items, string $idKey): bool
    {
        // Flatten nur dann, wenn die Top-Level-Elemente selbst Listen sind
        // und NICHT bereits die erwarteten Item-Strukturen mit $idKey enthalten.
        foreach ($items as $v) {
            if (! is_array($v)) {
                return false;
            }

            if (array_key_exists($idKey, $v)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Unterstützt itemPath mit genau EINER Wildcard-Stelle, z.B.:
     * - "*.Articles"            => ["*", "Articles"]
     * - "data.*.Articles"       => ["data", "Articles"]
     * - "data.groups.*.Articles"=> ["data.groups", "Articles"]
     *
     * Return: [parentListPath, childPath] oder null.
     */
    private function parseWildcardListChildPath(?string $itemPath): ?array
    {
        if (! $itemPath || $itemPath === '*') {
            return null;
        }

        // Match: [optional parent path] .*. [child path]
        // Examples:
        // "*.Articles" => parent="", child="Articles"
        // "data.*.Articles" => parent="data", child="Articles"
        if (preg_match('/^(?:(?<parent>.+)\.)?\*\.(?<child>.+)$/', $itemPath, $m) !== 1) {
            return null;
        }

        $parent = $m['parent'] ?? '';
        $child = $m['child'] ?? '';

        if ($child === '') {
            return null;
        }

        return [$parent !== '' ? $parent : '*', $child];
    }

    /**
     * external_key_field kann jetzt zusätzlich "parent.X" verwenden, um aus dem Parent-Item
     * (z.B. Artikelgruppe) zu lesen, wenn Items aus einer Nested-Liste (z.B. "*.Articles") kommen.
     */
    private function resolveExternalKey(?string $externalKeyField, array $recordPayload, ?array $parentItem, array $item): mixed
    {
        if (! $externalKeyField) {
            return null;
        }

        if ($parentItem !== null && str_starts_with($externalKeyField, 'parent.')) {
            return data_get($parentItem, substr($externalKeyField, strlen('parent.')));
        }

        // Fallback: zuerst Record-Level versuchen, dann Item-Level
        $recordLevel = data_get($recordPayload, $externalKeyField);
        if ($recordLevel !== null) {
            return $recordLevel;
        }

        return data_get($item, $externalKeyField);
    }

    private function log(?int $connectionId, int $endpointId, string $event, array $data, string $statusCode = '0', ?string $errorMessage = null): void
    {
        // api_connection_id ist required; wenn nicht vorhanden, lieber gar nicht loggen.
        if (! $connectionId) {
            return;
        }

        $shortError = $errorMessage;
        if ($shortError !== null && mb_strlen($shortError) > 240) {
            $shortError = mb_substr($shortError, 0, 240).'…';
        }

        ApiLog::create([
            'api_connection_id' => $connectionId,
            'endpoint_id' => $endpointId,
            'trigger' => 'SYSTEM',
            'request_data' => [
                'type' => 'orchestrator',
                'event' => $event,
            ],
            'response_data' => $data,
            'status_code' => $statusCode,
            'error_message' => $shortError,
        ]);
    }
}
