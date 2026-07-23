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
use Moox\Connect\Support\ApiEndpointRunner;
use Throwable;

final class RunEndpointJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private int $endpointId,
        private ?string $treeRunId = null,
        private bool $throwOnFailure = false,
    ) {
    }

    public function handle(ApiEndpointRunner $runner): void
    {
        /** @var ApiEndpoint|null $endpoint */
        $endpoint = ApiEndpoint::query()->with('apiConnection')->find($this->endpointId);

        if (! $endpoint) {
            return;
        }

        $result = $runner->run($endpoint, $this->treeRunId, $this->throwOnFailure);

        // ApiEndpointRunner sets the correct status semantics already (fetched/processed/failed).
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
                'endpoint_id' => $endpoint->id,
            ],
            'response_data' => [
                'event' => 'job_failed',
            ],
            'status_code' => '500',
            'error_message' => mb_substr($e->getMessage(), 0, 240),
        ]);
    }
}
