<?php

declare(strict_types=1);

namespace Moox\Connect\Jobs;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Moox\Connect\Models\ApiEndpoint;
use Moox\Connect\Models\ApiLog;
use Moox\Connect\Support\EndpointListToDetailOrchestrator;
use Throwable;

final class RunDetailForListJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private int $detailEndpointId,
        private ?string $treeRunId = null,
        private bool $throwOnFailure = false,
    ) {}

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
                'response_data' => [
                    'event' => 'job_done',
                    'created_jobs' => 0,
                ],
                'status_code' => '200',
                'error_message' => null,
            ]);

            return;
        }

        // Dispatch detail item jobs as a batch.
        // Keep options small (no then/catch closures), otherwise options can exceed mediumtext.
        $batch = Bus::batch($jobs)
            ->name(sprintf('connect:detail endpoint=%d', $endpoint->id))
            ->dispatch();

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
            'response_data' => [
                'event' => 'job_batch_dispatched',
                'created_jobs' => count($jobs),
                'batch_id' => $batch->id,
            ],
            'status_code' => '202',
            'error_message' => null,
        ]);
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
