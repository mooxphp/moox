<?php

declare(strict_types=1);

namespace Moox\Connect\Jobs;

use DateTimeInterface;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Moox\Connect\Models\ApiEndpoint;
use Moox\Connect\Models\ApiImportRecord;
use Moox\Connect\Models\ApiLog;
use Moox\Connect\Support\ApiEndpointRunner;
use Throwable;

final class RunEndpointForItemJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public int $maxExceptions = 3;

    public int $timeout = 180;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 300];

    public function __construct(
        private int $endpointId,
        private string $requestId,
        private ?string $externalKey = null,
        private ?string $treeRunId = null,
        private bool $throwOnFailure = false,
    ) {
        $this->onQueue('connect-detail');
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(30);
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        $lockKey = 'connect:run-endpoint-for-item:'.$this->endpointId.':'.$this->requestId;

        return [
            (new WithoutOverlapping($lockKey))
                ->releaseAfter(5)
                ->expireAfter(600),
        ];
    }

    public function handle(ApiEndpointRunner $runner): void
    {
        /** @var ApiEndpoint|null $endpoint */
        $endpoint = ApiEndpoint::query()->with('apiConnection')->find($this->endpointId);

        if (! $endpoint) {
            return;
        }

        // Endpoint für diese konkrete ID vorbereiten
        $endpointForCall = clone $endpoint;

        if ($endpoint->route_param_key) {
            $placeholder = '{'.$endpoint->route_param_key.'}';
            $endpointForCall->path = str_replace(
                $placeholder,
                $this->requestId,
                $endpoint->path
            );
        }

        $vars = $endpoint->variables ?? [];
        if ($endpoint->variable_key) {
            $vars[$endpoint->variable_key] = $this->requestId;
        }
        $endpointForCall->variables = $vars;

        // Detail-Items muessen pro requestId eindeutig sein (external_key = Item-ID),
        // Parent scope context remains available via sync_scope_hash.
        $scopeSourceKey = $this->externalKey ?? $this->requestId;
        $itemExternalKey = $this->requestId;
        $scopeHash = $scopeSourceKey !== null && $scopeSourceKey !== ''
            ? hash('sha256', (string) $scopeSourceKey)
            : null;

        $result = $this->runWithDeadlockRetry(
            fn (): array => $runner->run(
                $endpointForCall,
                $this->treeRunId,
                $this->throwOnFailure,
                $itemExternalKey,
                $scopeHash
            )
        );

        // external_key im erzeugten ImportRecord setzen
        if (isset($result['import_record_id'])) {
            $httpStatus = (int) ($result['status'] ?? 0);
            $isSuccessful = $httpStatus >= 200 && $httpStatus < 300;

            /** @var ApiImportRecord|null $importRecord */
            $importRecord = ApiImportRecord::query()->find((int) $result['import_record_id']);
            if ($importRecord) {
                $importRecord->external_key = $itemExternalKey;
                $importRecord->sync_scope_hash = ApiImportRecord::resolveIdentityScopeHash(
                    $scopeHash,
                    $itemExternalKey
                );
                // deterministisch: tree-run-id (oder fallback uuid, wenn manuell)
                $importRecord->sync_batch_id = $this->treeRunId ?: (string) Str::uuid();
                // Preserve status semantics set by ApiEndpointRunner (processed vs fetched/update).
                if (! $isSuccessful) {
                    $importRecord->status = 'failed';
                } else {
                    $currentStatus = strtolower((string) $importRecord->status);
                    if ($currentStatus === 'failed') {
                        // Fallback: in case runner marked it as failed but HTTP looks successful.
                        $importRecord->status = 'fetched';
                    }
                }
                $importRecord->error_message = $isSuccessful
                    ? null
                    : $importRecord->error_message;
                $importRecord->deleted_at = null;
                $importRecord->save();

                // For detail endpoints (single-object payloads) we may not go through FetchImportRecordsJob.
                // Dispatch the transform step here so destination_model writes can still happen.
                if (! empty($endpoint->destination_model)) {
                    TransformImportRecordsJob::dispatch(
                        $this->endpointId,
                        syncBatchId: $importRecord->sync_batch_id
                    );
                }
            }
        }
    }

    public function failed(Throwable $e): void
    {
        $endpoint = ApiEndpoint::query()->find($this->endpointId);
        if (! $endpoint?->api_connection_id) {
            return;
        }

        ApiLog::create([
            'api_connection_id' => $endpoint->api_connection_id,
            'endpoint_id' => $endpoint->id,
            'trigger' => 'SYSTEM',
            'request_data' => [
                'type' => 'job',
                'job' => self::class,
                'detail_endpoint_id' => $endpoint->id,
                'request_id' => $this->requestId,
            ],
            'response_data' => [
                'event' => 'job_failed',
            ],
            'status_code' => '500',
            'error_message' => mb_substr($e->getMessage(), 0, 240),
        ]);
    }

    /**
     * @param  \Closure(): array<string, mixed>  $callback
     * @return array<string, mixed>
     */
    private function runWithDeadlockRetry(\Closure $callback): array
    {
        $attempts = 3;
        $delaysInMicroseconds = [100000, 250000];

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return $callback();
            } catch (Throwable $e) {
                if (! $this->isDeadlockException($e) || $attempt >= $attempts) {
                    throw $e;
                }

                usleep($delaysInMicroseconds[$attempt - 1] ?? 300000);
            }
        }

        return $callback();
    }

    private function isDeadlockException(Throwable $exception): bool
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
}
