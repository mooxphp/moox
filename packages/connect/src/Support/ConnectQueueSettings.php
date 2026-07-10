<?php

declare(strict_types=1);

namespace Moox\Connect\Support;

final class ConnectQueueSettings
{
    public function __construct(
        public readonly string $queue,
        public readonly int $tries,
        public readonly int $timeout,
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
    }
}
