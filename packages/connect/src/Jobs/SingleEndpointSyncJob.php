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
use Moox\Connect\Models\ApiEndpoint;

final class SingleEndpointSyncJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private ?\Closure $responseCallback = null;

    public function __construct(
        private ApiConnection $api,
        private ApiEndpoint $endpoint,
        private array $parameters = []
    ) {}

    public function extend(\Closure $callback): self
    {
        $clone = clone $this;
        $clone->responseCallback = $callback;

        return $clone;
    }

    public function handle(): void
    {
        if (! $this->endpoint->isEnabled()) {
            throw JobException::configurationError(
                $this->job->getJobId(),
                "Endpoint {$this->endpoint->id} is disabled"
            );
        }

        if (! $this->api->isActive()) {
            throw JobException::configurationError(
                $this->job->getJobId(),
                "API connection {$this->api->id} is inactive"
            );
        }

        try {
            $response = $this->api->client()->executeEndpoint($this->endpoint, $this->parameters);

            if ($this->responseCallback) {
                ($this->responseCallback)($response);
            }
        } catch (\Throwable $e) {
            throw JobException::jobFailed(
                $this->job->getJobId(),
                "Sync failed: {$e->getMessage()}"
            );
        }
    }

    public function withParameters(array $parameters): self
    {
        $clone = clone $this;
        $clone->parameters = $parameters;

        return $clone;
    }
}
