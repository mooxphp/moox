<?php

declare(strict_types=1);

namespace Moox\Connect\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Moox\Connect\Models\ApiEndpoint;
use Moox\Connect\Models\ApiLog;
use Moox\Connect\Support\EndpointListToDetailOrchestrator;
use Moox\Connect\Traits\ConfiguresConnectQueue;

final class FinalizeDetailSyncJob implements ShouldQueue
{
    use ConfiguresConnectQueue;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public array $backoff = [15, 30, 60, 120];

    /**
     * @param  array<string, array<int, string>>  $seenKeysByScope
     */
    public function __construct(
        private int $detailEndpointId,
        private string $batchId,
        private array $seenKeysByScope,
    ) {
        $this->configureConnectQueue('finalize_detail', $this->detailEndpointId);
    }

    public function handle(EndpointListToDetailOrchestrator $orchestrator): void
    {
        $batch = Bus::findBatch($this->batchId);

        if ($batch === null || ! $batch->finished()) {
            $this->release(15);

            return;
        }

        /** @var ApiEndpoint|null $endpoint */
        $endpoint = ApiEndpoint::query()->find($this->detailEndpointId);

        if (! $endpoint) {
            return;
        }

        if ($batch->cancelled() || $batch->failedJobs > 0) {
            ApiLog::create([
                'api_connection_id' => $endpoint->api_connection_id,
                'endpoint_id' => $endpoint->id,
                'trigger' => 'SYSTEM',
                'request_data' => [
                    'type' => 'job',
                    'job' => self::class,
                    'batch_id' => $this->batchId,
                ],
                'response_data' => [
                    'event' => 'detail_sync_prune_skipped',
                    'failed_jobs' => $batch->failedJobs,
                ],
                'status_code' => '409',
                'error_message' => 'Prune skipped because the replacement batch did not finish cleanly.',
            ]);

            return;
        }

        $orchestrator->pruneMissingDetailRecords($endpoint, $this->seenKeysByScope);

        ApiLog::create([
            'api_connection_id' => $endpoint->api_connection_id,
            'endpoint_id' => $endpoint->id,
            'trigger' => 'SYSTEM',
            'request_data' => [
                'type' => 'job',
                'job' => self::class,
                'batch_id' => $this->batchId,
            ],
            'response_data' => [
                'event' => 'detail_sync_prune_completed',
                'scopes' => count($this->seenKeysByScope),
            ],
            'status_code' => '200',
            'error_message' => null,
        ]);
    }
}
