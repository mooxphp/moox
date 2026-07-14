<?php

declare(strict_types=1);

namespace Moox\Connect\Support;

use DateTimeInterface;

final class ConnectQueueSettings
{
    /**
     * @param  array<int, int>  $backoff
     * @param  array<int, int>  $deadlockRetryDelaysMs
     */
    public function __construct(
        public readonly string $queue,
        public readonly int $tries,
        public readonly int $timeout,
        public readonly int $maxExceptions,
        public readonly array $backoff,
        public readonly int $retryUntilMinutes,
        public readonly int $overlapReleaseAfter,
        public readonly int $overlapExpireBuffer,
        public readonly int $overlapExpireMin,
        public readonly int $deadlockRetryAttempts,
        public readonly array $deadlockRetryDelaysMs,
    ) {}

    public function applyTo(object $job): void
    {
        if (method_exists($job, 'onQueue')) {
            $job->onQueue($this->queue);
        }

        if (property_exists($job, 'tries')) {
            $job->tries = $this->tries;
        }

        if (property_exists($job, 'timeout')) {
            $job->timeout = $this->timeout;
        }

        if (property_exists($job, 'maxExceptions')) {
            $job->maxExceptions = $this->maxExceptions;
        }

        if (property_exists($job, 'backoff')) {
            $job->backoff = $this->backoff;
        }
    }

    public function retryUntil(): ?DateTimeInterface
    {
        if ($this->retryUntilMinutes <= 0) {
            return null;
        }

        return now()->addMinutes($this->retryUntilMinutes);
    }

    public function overlapExpireAfter(): int
    {
        if ($this->overlapExpireBuffer <= 0 && $this->overlapExpireMin <= 0) {
            return max($this->timeout + 60, 300);
        }

        $fromTimeout = $this->overlapExpireBuffer > 0
            ? $this->timeout + $this->overlapExpireBuffer
            : $this->timeout;

        if ($this->overlapExpireMin <= 0) {
            return $fromTimeout;
        }

        return max($fromTimeout, $this->overlapExpireMin);
    }
}
