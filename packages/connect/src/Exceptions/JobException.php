<?php

declare(strict_types=1);

namespace Moox\Connect\Exceptions;

use RuntimeException;

final class JobException extends RuntimeException
{
    private string $jobId;

    public static function jobFailed(string $jobId, string $reason): self
    {
        $instance = new self("Job {$jobId} failed: {$reason}");
        $instance->jobId = $jobId;

        return $instance;
    }

    public static function invalidState(string $jobId, string $expected, string $actual): self
    {
        $instance = new self("Job {$jobId} in invalid state: expected {$expected}, got {$actual}");
        $instance->jobId = $jobId;

        return $instance;
    }

    public static function configurationError(string $jobId, string $reason): self
    {
        $instance = new self("Job {$jobId} configuration error: {$reason}");
        $instance->jobId = $jobId;

        return $instance;
    }

    public function getJobId(): string
    {
        return $this->jobId;
    }
}
