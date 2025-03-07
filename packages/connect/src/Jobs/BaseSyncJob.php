<?php

declare(strict_types=1);

namespace Moox\Connect\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Moox\Connect\Contracts\ApiClientInterface;
use Moox\Connect\Support\JobStatusManager;
use RuntimeException;

abstract class BaseSyncJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected string $jobId;

    protected ApiClientInterface $client;

    protected JobStatusManager $statusManager;

    protected string $apiId;

    protected int $maxRetries;

    protected string $retryStrategy;

    protected int $retryDelay;

    protected bool $notifyOnFailure;

    protected ?int $freshnessTtl;

    protected ?Carbon $lastFreshAt;

    public function __construct(
        string $jobId,
        string $apiId,
        ApiClientInterface $client,
        JobStatusManager $statusManager,
        int $maxRetries = 3,
        int $retryDelay = 300,
        string $retryStrategy = 'exponential',
        bool $notifyOnFailure = true,
        ?int $freshnessTtl = null
    ) {
        $this->jobId = $jobId;
        $this->apiId = $apiId;
        $this->client = $client;
        $this->statusManager = $statusManager;
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $retryDelay;
        $this->retryStrategy = $retryStrategy;
        $this->notifyOnFailure = $notifyOnFailure;
        $this->freshnessTtl = $freshnessTtl;
    }

    abstract protected function execute(): void;

    public function handle(): void
    {
        if (! $this->shouldExecute()) {
            return;
        }

        try {
            $this->statusManager->markStarted($this->jobId);

            if (! $this->client->isHealthy($this->apiId)) {
                throw new RuntimeException("API {$this->apiId} is not healthy");
            }

            $this->execute();

            $this->statusManager->markCompleted($this->jobId);
            $this->updateLastFreshAt();
        } catch (\Throwable $e) {
            $this->handleError($e);

            if (! $this->shouldRetry()) {
                $this->statusManager->markFailed($this->jobId, $e->getMessage());
                if ($this->notifyOnFailure) {
                    $this->notifyFailure($e);
                }
                throw $e;
            }

            $nextRetry = $this->calculateNextRetry();
            $this->statusManager->markRetrying($this->jobId, $nextRetry);

            $this->release($this->calculateRetryDelay());
        }
    }

    private function shouldExecute(): bool
    {
        if ($this->freshnessTtl === null) {
            return true;
        }

        if ($this->lastFreshAt === null) {
            return true;
        }

        return $this->lastFreshAt->addSeconds($this->freshnessTtl)->isPast();
    }

    private function shouldRetry(): bool
    {
        return $this->attempts() < $this->maxRetries;
    }

    private function calculateNextRetry(): Carbon
    {
        $delay = $this->calculateRetryDelay();

        return Carbon::now()->addSeconds($delay);
    }

    private function calculateRetryDelay(): int
    {
        return match ($this->retryStrategy) {
            'linear' => $this->retryDelay,
            'exponential' => $this->retryDelay * (2 ** ($this->attempts() - 1)),
            default => throw new RuntimeException("Invalid retry strategy: {$this->retryStrategy}")
        };
    }

    private function updateLastFreshAt(): void
    {
        $this->lastFreshAt = Carbon::now();
    }

    protected function handleError(\Throwable $e): void
    {
        // Override in child classes for custom error handling
    }

    protected function notifyFailure(\Throwable $e): void
    {
        // Override in child classes for custom notifications
    }

    public function getJobId(): string
    {
        return $this->jobId;
    }

    public function getApiId(): string
    {
        return $this->apiId;
    }
}
