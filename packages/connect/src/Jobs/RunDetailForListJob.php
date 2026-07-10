<?php

declare(strict_types=1);

namespace Moox\Connect\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Moox\Connect\Models\ApiEndpoint;
use Moox\Connect\Models\ApiLog;
use Moox\Connect\Support\EndpointListToDetailOrchestrator;
use Moox\Connect\Support\ConnectQueueSettingsResolver;
use Moox\Connect\Traits\ConfiguresConnectQueue;
use Throwable;

final class RunDetailForListJob implements ShouldQueue
{
    use Batchable;
    use ConfiguresConnectQueue;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private int $detailEndpointId,
        private ?string $treeRunId = null,
        private bool $throwOnFailure = false,
    ) {
        $this->configureConnectQueue('detail_list', $this->detailEndpointId);
    }

    public function handle(EndpointListToDetailOrchestrator $orchestrator): void
    {
        /** @var ApiEndpoint|null $endpoint */
        $endpoint = ApiEndpoint::query()->with('apiConnection')->find($this->detailEndpointId);

        if (! $endpoint) {
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
            ],
            'response_data' => [
                'event' => 'job_start',
            ],
            'status_code' => '0',
            'error_message' => null,
        ]);

        $built = $orchestrator->buildDetailJobs($endpoint, $this->treeRunId, $this->treeRunId, $this->throwOnFailure);
        $jobs = $built['jobs'] ?? [];
        $seenKeysByScope = $built['seen'] ?? [];
        $meta = $built['meta'] ?? [];
        $syncMode = $endpoint->sync_mode ?: 'append';

        if ($jobs === []) {
            ApiLog::create([
                'api_connection_id' => $endpoint->api_connection_id,
                'endpoint_id' => $endpoint->id,
                'trigger' => 'SYSTEM',
                'request_data' => [
                    'type' => 'job',
                    'job' => self::class,
                    'detail_endpoint_id' => $endpoint->id,
                ],
                'response_data' => array_merge([
                    'event' => 'job_done',
                    'created_jobs' => 0,
                ], $meta),
                'status_code' => '200',
                'error_message' => null,
            ]);

            return;
        }

        // Dispatch detail item jobs as a batch (chunked inserts to avoid MySQL placeholder limits).
        $queue = app(ConnectQueueSettingsResolver::class)
            ->resolve('detail_item', $endpoint->id)
            ->queue;

        $batch = $orchestrator->dispatchDetailJobsBatch(
            sprintf('connect:detail endpoint=%d', $endpoint->id),
            $jobs,
            $queue,
        );

        if ($syncMode === 'sync') {
            FinalizeDetailSyncJob::dispatch(
                $endpoint->id,
                $batch->id,
                $seenKeysByScope
            );
        }

        ApiLog::create([
            'api_connection_id' => $endpoint->api_connection_id,
            'endpoint_id' => $endpoint->id,
            'trigger' => 'SYSTEM',
            'request_data' => [
                'type' => 'job',
                'job' => self::class,
                'detail_endpoint_id' => $endpoint->id,
            ],
            'response_data' => array_merge([
                'event' => 'job_batch_dispatched',
                'created_jobs' => count($jobs),
                'batch_id' => $batch->id,
                'queue_insert_chunks' => (int) max(1, (int) ceil(count($jobs) / EndpointListToDetailOrchestrator::QUEUE_BATCH_CHUNK_SIZE)),
                'request_id_sample' => $this->sampleRequestIds($seenKeysByScope),
            ], $meta),
            'status_code' => '202',
            'error_message' => null,
        ]);
    }

    /**
     * @param  array<string, array<int, string>>  $seenKeysByScope
     * @return array<int, string>
     */
    private function sampleRequestIds(array $seenKeysByScope, int $limit = 20): array
    {
        $ids = [];

        foreach ($seenKeysByScope as $keys) {
            foreach ($keys as $key) {
                $ids[] = (string) $key;
            }
        }

        return array_slice(array_values(array_unique($ids)), 0, $limit);
    }

    public function failed(Throwable $e): void
    {
        $endpoint = ApiEndpoint::query()->find($this->detailEndpointId);
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
            ],
            'response_data' => [
                'event' => 'job_failed',
            ],
            'status_code' => '500',
            'error_message' => mb_substr($e->getMessage(), 0, 240),
        ]);
    }
}
