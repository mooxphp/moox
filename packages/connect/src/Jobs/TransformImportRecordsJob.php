<?php

declare(strict_types=1);

namespace Moox\Connect\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Moox\Connect\Models\ApiEndpoint;
use Moox\Connect\Models\ApiImportRecord;
use Moox\Connect\Support\ApiImportPayloadExtractor;
use Moox\Connect\Support\TransformerRegistry;
use Moox\Connect\Traits\ConfiguresConnectQueue;

final class TransformImportRecordsJob implements ShouldQueue
{
    use ConfiguresConnectQueue;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Seconds after which a stuck `processing` record can be re-claimed.
     *
     * This allows recovery if a worker crashes after claiming.
     */
    private const PROCESSING_STALE_TTL_SECONDS = 600;

    /**
     * Max. rows per destination SQL statement (bulk upsert / insert).
     */
    private const DESTINATION_WRITE_CHUNK_SIZE = 100;

    /**
     * Retry count for deadlocks in the claim phase.
     */
    private const CLAIM_DEADLOCK_RETRY_ATTEMPTS = 3;

    public function __construct(
        private int $endpointId,
        private int $batchSize = 100,
        private ?string $syncBatchId = null
    ) {
        $this->configureConnectQueue('transform', $this->endpointId);
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        $batchKey = $this->syncBatchId ?? 'all';
        $lockKey = "connect:transform:endpoint:{$this->endpointId}:batch:{$batchKey}";

        return [
            (new WithoutOverlapping($lockKey))
                ->releaseAfter(5)
                ->expireAfter(120),
        ];
    }

    public function handle(
        TransformerRegistry $transformers,
        ApiImportPayloadExtractor $payloadExtractor
    ): void {
        /** @var ApiEndpoint $endpoint */
        $endpoint = ApiEndpoint::query()->findOrFail($this->endpointId);

        if (! $endpoint->destination_model) {
            // Kein Zielmodell konfiguriert, nichts zu tun
            return;
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $endpoint->destination_model;

        /** @var Model $destinationModel */
        $destinationModel = new $modelClass;

        $fieldMappings = $endpoint->field_mappings ?? [];
        $keyFields = $endpoint->key_fields ?? [];
        $syncMode = $endpoint->sync_mode ?: 'append';
        $syncScopeFields = $endpoint->sync_scope_fields ?? [];

        // Optional generic lookup injection (join-like behavior via endpoint.options).
        // Generic option names:
        // - lookup.endpoint_id  : int    - endpoint id to build the lookup map from
        // - lookup.list_path    : string - JSON path to the list in the lookup payload
        // - lookup.key_path     : string - JSON path of the join key in each lookup item
        // - lookup.value_path   : string - JSON path of the value to inject from each lookup item
        // - source.key_path     : string - JSON path of the join key in the source payload
        // - source.target_path  : string - JSON path where the injected value should be written
        $options = $endpoint->options ?? [];
        $lookupEndpointId = $options['lookup.endpoint_id'] ?? null;
        $lookupListPath = $options['lookup.list_path'] ?? null;
        $lookupKeyPath = $options['lookup.key_path'] ?? null;
        $lookupValuePath = $options['lookup.value_path'] ?? null;
        $sourceKeyPath = $options['source.key_path'] ?? null;
        $sourceTargetPath = $options['source.target_path'] ?? null;

        $hasLookupInjection = is_numeric($lookupEndpointId)
            && is_string($lookupListPath) && $lookupListPath !== ''
            && is_string($lookupKeyPath) && $lookupKeyPath !== ''
            && is_string($lookupValuePath) && $lookupValuePath !== ''
            && is_string($sourceKeyPath) && $sourceKeyPath !== ''
            && is_string($sourceTargetPath) && $sourceTargetPath !== '';

        $lookupMap = [];
        if ($hasLookupInjection) {
            /** @var array<string, array<string, string>> $lookupCache */
            static $lookupCache = [];
            $cacheKey = (string) $lookupEndpointId;

            if (isset($lookupCache[$cacheKey])) {
                $lookupMap = $lookupCache[$cacheKey];
            } else {
                $lookupRecord = ApiImportRecord::query()
                    ->where('api_endpoint_id', (int) $lookupEndpointId)
                    ->orderByDesc('id')
                    ->first();

                if ($lookupRecord) {
                    $lookupPayload = $payloadExtractor->reconstructPayload($lookupRecord);

                    $list = Arr::get($lookupPayload, $lookupListPath);

                    // Generic wildcard support:
                    // Laravel's wildcard/path resolution isn't guaranteed to work the same way
                    // for all payload shapes. If the list_path starts with "*.", interpret it
                    // as: "for each top-level list element, take the sub-path after '*.'".
                    //
                    // Example: "*.Products" on a list of objects => flatten all `Products`.
                    if ($list === null
                        && is_string($lookupListPath)
                        && str_starts_with($lookupListPath, '*.')) {
                        $suffix = substr($lookupListPath, 2); // remove "*."

                        if (is_array($lookupPayload) && array_is_list($lookupPayload)) {
                            $list = [];
                            foreach ($lookupPayload as $entry) {
                                if (! is_array($entry)) {
                                    continue;
                                }

                                $sub = Arr::get($entry, $suffix);
                                if (is_array($sub)) {
                                    $list[] = $sub;
                                }
                            }
                        }
                    }
                    if (array_is_list($lookupPayload) && ! is_array($list)) {
                        $list = $lookupPayload;
                    }

                    if (is_array($list)) {
                        foreach ($this->normalizeLookupItems($list) as $item) {
                            if (! is_array($item)) {
                                continue;
                            }

                            $key = Arr::get($item, $lookupKeyPath);
                            $value = Arr::get($item, $lookupValuePath);

                            if ($key === null || $value === null) {
                                continue;
                            }

                            $lookupMap[(string) $key] = (string) $value;
                        }
                    }
                }

                $lookupCache[$cacheKey] = $lookupMap;
            }
        }

        // Candidate statuses for transformation.
        // `processing` is included only if it's stale (worker crash recovery).
        $candidateStatuses = ['new', 'fetched', 'update'];
        $staleProcessingCutoff = now()->subSeconds(self::PROCESSING_STALE_TTL_SECONDS);

        $claimedIds = [];

        // Claim phase: atomically select rows that no other worker is processing.
        // We use:
        // - status in ('new','fetched','update')
        // - OR status='processing' but stale
        // - row lock with SKIP LOCKED to avoid deadlocks between workers
        try {
            $this->runClaimWithDeadlockRetry(function () use (
                &$claimedIds,
                $endpoint,
                $candidateStatuses,
                $staleProcessingCutoff
            ): void {
                DB::transaction(function () use (
                    &$claimedIds,
                    $endpoint,
                    $candidateStatuses,
                    $staleProcessingCutoff
                ): void {
                    $ids = ApiImportRecord::query()
                        ->select('id')
                        ->where('api_endpoint_id', $endpoint->id)
                        ->where(function ($inner) use ($candidateStatuses, $staleProcessingCutoff): void {
                            $inner->whereIn('status', $candidateStatuses)
                                ->orWhere(function ($inner2) use ($staleProcessingCutoff): void {
                                    $inner2->where('status', 'processing')
                                        ->where('updated_at', '<', $staleProcessingCutoff);
                                });
                        })
                        ->when(
                            $this->syncBatchId !== null,
                            fn ($q) => $q->where('sync_batch_id', $this->syncBatchId)
                        )
                        ->orderBy('id')
                        ->limit($this->batchSize)
                        ->lock('FOR UPDATE SKIP LOCKED')
                        ->pluck('id')
                        ->all();

                    $claimedIds = $ids;

                    if ($claimedIds === []) {
                        return;
                    }

                    ApiImportRecord::query()
                        ->whereIn('id', $claimedIds)
                        ->update([
                            'status' => 'processing',
                            'error_message' => null,
                            'updated_at' => now(),
                        ]);
                });
            });
        } catch (\Throwable $e) {
            if ($this->isDeadlockException($e)) {
                // Keep the queue healthy under load: defer instead of failing hard.
                $this->release(15);

                return;
            }

            throw $e;
        }

        if ($claimedIds === []) {
            return;
        }

        // Process phase: write to destination model outside of the claim transaction.
        // This keeps the `api_import_records` locks short-lived.
        /** @var Collection<int, ApiImportRecord> $records */
        $records = ApiImportRecord::query()
            ->whereIn('id', $claimedIds)
            ->get();

        $uniqueBy = array_keys($keyFields);
        /** @var list<array{record: ApiImportRecord, where: array<string, mixed>, data: array<string, mixed>, merged: array<string, mixed>}> $upsertItems */
        $upsertItems = [];
        /** @var list<array{record: ApiImportRecord, data: array<string, mixed>}> $createItems */
        $createItems = [];

        foreach ($records as $record) {
            try {
                $this->heartbeatClaimedRecords($claimedIds);

                $payload = $payloadExtractor->reconstructPayload($record);
                if (! is_array($payload)) {
                    throw new \RuntimeException('Reconstructed payload is not an array');
                }

                if ($hasLookupInjection && $lookupMap !== []) {
                    $sourceKey = Arr::get($payload, $sourceKeyPath);
                    if ($sourceKey !== null && $sourceKey !== '') {
                        $injected = $lookupMap[(string) $sourceKey] ?? null;
                        if ($injected !== null) {
                            data_set($payload, $sourceTargetPath, $injected);
                        }
                    }
                }

                $data = $this->buildDataArray($payload, $fieldMappings, $transformers);
                $where = $this->buildWhereArray($payload, $keyFields, $transformers);

                if ($keyFields !== [] && $where !== []) {
                    foreach ($keyFields as $internalField => $_) {
                        if (array_key_exists($internalField, $where) && $where[$internalField] === null) {
                            throw new \RuntimeException("Missing key field value for {$internalField}");
                        }
                    }
                }

                if (! empty($where)) {
                    $merged = array_merge($where, $data);
                    $merged = $this->filterAttributesForDestinationModel($destinationModel, $merged);
                    $upsertItems[] = [
                        'record' => $record,
                        'where' => $where,
                        'data' => $data,
                        'merged' => $merged,
                    ];
                } else {
                    $createItems[] = [
                        'record' => $record,
                        'data' => $this->filterAttributesForDestinationModel($destinationModel, $data),
                    ];
                }
            } catch (\Throwable $e) {
                $record->status = 'failed';
                $record->error_message = $e->getMessage();
                $record->save();
            }
        }

        if ($upsertItems !== [] && $uniqueBy !== []) {
            foreach (array_chunk($upsertItems, self::DESTINATION_WRITE_CHUNK_SIZE) as $chunk) {
                $values = array_map(
                    static fn (array $item): array => $item['merged'],
                    $chunk
                );

                $firstKeys = array_keys($values[0]);
                $updateColumns = array_values(array_diff($firstKeys, $uniqueBy));
                if ($updateColumns === []) {
                    $updateColumns = $firstKeys;
                }

                try {
                    $modelClass::upsert($values, $uniqueBy, $updateColumns);

                    $this->markImportRecordsAsProcessed(
                        array_map(static fn (array $item): int => $item['record']->id, $chunk)
                    );
                } catch (\Throwable) {
                    $successIds = [];

                    foreach ($chunk as $item) {
                        try {
                            $modelClass::updateOrCreate($item['where'], $item['data']);
                            $successIds[] = $item['record']->id;
                        } catch (\Throwable $inner) {
                            $item['record']->status = 'failed';
                            $item['record']->error_message = $inner->getMessage();
                            $item['record']->save();
                        }
                    }

                    $this->markImportRecordsAsProcessed($successIds);
                }
            }
        }

        if ($createItems !== []) {
            foreach (array_chunk($createItems, self::DESTINATION_WRITE_CHUNK_SIZE) as $chunk) {
                $rows = [];
                $now = now();

                foreach ($chunk as $item) {
                    $row = $item['data'];
                    if ($destinationModel->usesTimestamps()) {
                        $row[$destinationModel->getCreatedAtColumn()] = $now;
                        $row[$destinationModel->getUpdatedAtColumn()] = $now;
                    }

                    $rows[] = $row;
                }

                try {
                    DB::table($destinationModel->getTable())->insert($rows);

                    $this->markImportRecordsAsProcessed(
                        array_map(static fn (array $item): int => $item['record']->id, $chunk)
                    );
                } catch (\Throwable) {
                    $successIds = [];

                    foreach ($chunk as $item) {
                        try {
                            $modelClass::create($item['data']);
                            $successIds[] = $item['record']->id;
                        } catch (\Throwable $inner) {
                            $item['record']->status = 'failed';
                            $item['record']->error_message = $inner->getMessage();
                            $item['record']->save();
                        }
                    }

                    $this->markImportRecordsAsProcessed($successIds);
                }
            }
        }

        if ($syncMode !== 'sync') {
            $this->dispatchNextBatchIfRemaining(
                $endpoint->id,
                $candidateStatuses,
                $staleProcessingCutoff
            );

            return;
        }

        // Prune: nur sinnvoll, wenn:
        // - wir genau EIN Key-Feld im Zielmodell haben (whereNotIn)
        // - und ein syncBatchId zum Partitionieren gegeben ist
        // Andernfalls könnte paralleles Prune zu falschen Deletes führen.
        if ($this->syncBatchId === null) {
            $this->dispatchNextBatchIfRemaining(
                $endpoint->id,
                $candidateStatuses,
                $staleProcessingCutoff
            );

            return;
        }

        if (count($keyFields) !== 1) {
            $this->dispatchNextBatchIfRemaining(
                $endpoint->id,
                $candidateStatuses,
                $staleProcessingCutoff
            );

            return;
        }

        $lockName = sprintf(
            'connect:transform:prune:endpoint=%d:sync_batch_id=%s',
            $endpoint->id,
            $this->syncBatchId
        );

        $lockRows = DB::select('SELECT GET_LOCK(?, 10) AS l', [$lockName]);
        $acquired = (int) (($lockRows[0]->l ?? 0));

        if ($acquired !== 1) {
            $this->dispatchNextBatchIfRemaining(
                $endpoint->id,
                $candidateStatuses,
                $staleProcessingCutoff
            );

            return;
        }

        try {
            // Nur prunen, wenn gerade keine Kandidaten oder processing Datensätze übrig sind.
            $remaining = ApiImportRecord::query()
                ->where('api_endpoint_id', $endpoint->id)
                ->where('sync_batch_id', $this->syncBatchId)
                ->whereIn('status', ['new', 'fetched', 'update', 'processing'])
                ->exists();

            if ($remaining) {
                return;
            }

            $targetKeyField = array_key_first($keyFields);
            $processedQuery = ApiImportRecord::query()
                ->where('api_endpoint_id', $endpoint->id)
                ->where('status', 'processed')
                ->where('sync_batch_id', $this->syncBatchId);

            $processedQuery->select(['id', 'payload']);

            $byScope = [];
            $processedQuery->chunk(500, function (Collection $records) use (&$byScope, $keyFields, $syncScopeFields, $payloadExtractor, $transformers): void {
                foreach ($records as $record) {
                    /** @var ApiImportRecord $record */
                    $payload = $payloadExtractor->reconstructPayload($record);
                    if (! is_array($payload)) {
                        continue;
                    }

                    $where = $this->buildWhereArray($payload, $keyFields, $transformers);
                    $keyValue = $where[array_key_first($keyFields)] ?? null;
                    if ($keyValue === null) {
                        continue;
                    }

                    $scope = [];
                    foreach ((array) $syncScopeFields as $internal => $externalPath) {
                        $scope[$internal] = Arr::get($payload, $externalPath);
                    }

                    $scopeKey = json_encode($scope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $byScope[$scopeKey]['scope'] = $scope;
                    $byScope[$scopeKey]['keys'][] = $keyValue;
                }
            });

            foreach ($byScope as $group) {
                $scope = $group['scope'] ?? [];
                $keys = array_values(array_unique($group['keys'] ?? []));
                if ($keys === []) {
                    continue;
                }

                $q = $modelClass::query();
                foreach ($scope as $internal => $value) {
                    // wenn scope value null ist, skippen (sonst löschen wir u.U. alles)
                    if ($value === null) {
                        continue 2;
                    }
                    $q->where($internal, $value);
                }

                $q->whereNotIn($targetKeyField, $keys)->delete();
            }
        } finally {
            DB::select('SELECT RELEASE_LOCK(?)', [$lockName]);
        }

        $this->dispatchNextBatchIfRemaining(
            $endpoint->id,
            $candidateStatuses,
            $staleProcessingCutoff
        );
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function markImportRecordsAsProcessed(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        ApiImportRecord::query()
            ->whereIn('id', $ids)
            ->update([
                'status' => 'processed',
                'error_message' => null,
                'updated_at' => now(),
            ]);
    }

    /**
     * @param  array<int, int>  $claimedIds
     */
    private function heartbeatClaimedRecords(array $claimedIds): void
    {
        if ($claimedIds === []) {
            return;
        }

        ApiImportRecord::query()
            ->whereIn('id', $claimedIds)
            ->where('status', 'processing')
            ->update([
                'updated_at' => now(),
            ]);
    }

    /**
     * @param  \Closure(): void  $callback
     */
    private function runClaimWithDeadlockRetry(\Closure $callback): void
    {
        $delaysInMicroseconds = [100000, 250000];

        for ($attempt = 1; $attempt <= self::CLAIM_DEADLOCK_RETRY_ATTEMPTS; $attempt++) {
            try {
                $callback();

                return;
            } catch (\Throwable $e) {
                if (! $this->isDeadlockException($e) || $attempt >= self::CLAIM_DEADLOCK_RETRY_ATTEMPTS) {
                    throw $e;
                }

                usleep($delaysInMicroseconds[$attempt - 1] ?? 300000);
            }
        }
    }

    private function isDeadlockException(\Throwable $exception): bool
    {
        if ($exception instanceof QueryException) {
            $code = (string) $exception->getCode();
            $message = strtolower($exception->getMessage());

            return $code === '40001'
                || str_contains($message, 'deadlock found')
                || str_contains($message, 'serialization failure');
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function filterAttributesForDestinationModel(Model $model, array $attributes): array
    {
        $fillable = $model->getFillable();
        if ($fillable === []) {
            return $attributes;
        }

        return array_intersect_key($attributes, array_flip($fillable));
    }

    private function buildDataArray(array $payload, array $fieldMappings, TransformerRegistry $transformers): array
    {
        $data = [];

        foreach ($fieldMappings as $mapping) {
            if (! isset($mapping['external_field'], $mapping['internal_field'])) {
                continue;
            }

            $value = Arr::get($payload, $mapping['external_field']);

            if (array_key_exists('transformer', $mapping) && $mapping['transformer']) {
                $options = $mapping['transformer_options'] ?? [];
                $value = $transformers->transform($value, $options, $mapping['transformer']);
            }

            $data[$mapping['internal_field']] = $value;
        }

        return $data;
    }

    private function buildWhereArray(array $payload, array $keyFields, TransformerRegistry $transformers): array
    {
        $where = [];

        foreach ($keyFields as $internalField => $externalFieldSpec) {
            $externalPath = null;
            $transformer = null;
            $transformerOptions = [];

            // Backward compatible:
            // - string => JSON path directly (e.g. "ArticleNumber")
            // - array  => {"external_field": "...", "transformer": "...", "transformer_options": {...}}
            if (is_string($externalFieldSpec)) {
                $externalPath = $externalFieldSpec;
            } elseif (is_array($externalFieldSpec)) {
                $externalPath = $externalFieldSpec['external_field'] ?? null;
                $transformer = $externalFieldSpec['transformer'] ?? null;
                $transformerOptions = $externalFieldSpec['transformer_options'] ?? [];
            }

            if (! is_string($externalPath) || $externalPath === '') {
                $where[$internalField] = null;

                continue;
            }

            $value = Arr::get($payload, $externalPath);

            if ($transformer && is_string($transformer) && $transformer !== '') {
                $value = $transformers->transform($value, $transformerOptions, $transformer);
            }

            $where[$internalField] = $value;
        }

        return $where;
    }

    /**
     * @param  array<int, string>  $candidateStatuses
     */
    private function dispatchNextBatchIfRemaining(
        int $endpointId,
        array $candidateStatuses,
        \DateTimeInterface $staleProcessingCutoff
    ): void {
        $remaining = ApiImportRecord::query()
            ->where('api_endpoint_id', $endpointId)
            ->where(function ($inner) use ($candidateStatuses, $staleProcessingCutoff): void {
                $inner->whereIn('status', $candidateStatuses)
                    ->orWhere(function ($inner2) use ($staleProcessingCutoff): void {
                        $inner2->where('status', 'processing')
                            ->where('updated_at', '<', $staleProcessingCutoff);
                    });
            })
            ->when(
                $this->syncBatchId !== null,
                fn ($q) => $q->where('sync_batch_id', $this->syncBatchId)
            )
            ->exists();

        if (! $remaining) {
            return;
        }

        self::dispatch(
            $endpointId,
            $this->batchSize,
            $this->syncBatchId
        );
    }

    /**
     * Normalize arbitrarily nested lookup list structures into plain associative records.
     *
     * Supports:
     * - list of associative items
     * - associative single item
     * - list of lists (one or many levels)
     * - mixed nesting depending on endpoint relationship shapes
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeLookupItems(array $input): array
    {
        $result = [];
        $stack = [$input];

        while ($stack !== []) {
            $current = array_pop($stack);

            if (! is_array($current)) {
                continue;
            }

            if (! array_is_list($current)) {
                $result[] = $current;

                continue;
            }

            foreach ($current as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                if (array_is_list($entry)) {
                    $stack[] = $entry;
                } else {
                    $result[] = $entry;
                }
            }
        }

        return $result;
    }
}
