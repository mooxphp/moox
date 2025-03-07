<?php

declare(strict_types=1);

namespace Moox\Connect\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Moox\Connect\Exceptions\JobException;
use Moox\Connect\Models\ApiConnection;

final class BatchSyncJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private ApiConnection $api,
        private array $endpointIds,
        private array $parameters = []
    ) {}

    public function handle(): void
    {
        if (! $this->api->isActive()) {
            throw JobException::configurationError(
                $this->job->getJobId(),
                "API connection {$this->api->id} is inactive"
            );
        }

        if (empty($this->endpointIds)) {
            throw JobException::configurationError(
                $this->job->getJobId(),
                'No endpoints specified for batch sync'
            );
        }

        $endpoints = $this->api->endpoints()->whereIn('id', $this->endpointIds)->get();

        if ($endpoints->isEmpty()) {
            throw JobException::configurationError(
                $this->job->getJobId(),
                'No valid endpoints found for batch sync'
            );
        }

        $disabledEndpoints = $endpoints->reject(fn ($endpoint) => $endpoint->isEnabled());
        if ($disabledEndpoints->isNotEmpty()) {
            throw JobException::configurationError(
                $this->job->getJobId(),
                'Some endpoints are disabled: '.$disabledEndpoints->pluck('id')->join(', ')
            );
        }

        try {
            foreach ($endpoints as $endpoint) {
                $this->api->client()->executeEndpoint($endpoint, $this->parameters);
            }
        } catch (\Throwable $e) {
            throw JobException::jobFailed(
                $this->job->getJobId(),
                "Batch sync failed: {$e->getMessage()}"
            );
        }
    }
}
