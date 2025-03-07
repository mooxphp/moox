<?php

declare(strict_types=1);

namespace Moox\Connect\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

final class JobStatusManager
{
    private string $cachePrefix;

    private int $ttl;

    public function __construct(
        string $cachePrefix = 'job_status:',
        int $ttl = 86400
    ) {
        $this->cachePrefix = $cachePrefix;
        $this->ttl = $ttl;
    }

    public function create(string $jobId): JobStatus
    {
        $status = new JobStatus($jobId);
        $this->store($status);

        return $status;
    }

    public function get(string $jobId): JobStatus
    {
        $data = Cache::get($this->getCacheKey($jobId));

        if ($data === null) {
            throw new RuntimeException("No status found for job: {$jobId}");
        }

        return JobStatus::fromArray($data);
    }

    public function update(JobStatus $status): void
    {
        $this->store($status);
    }

    public function markStarted(string $jobId): JobStatus
    {
        return $this->updateStatus(
            $jobId,
            JobStatus::STATUS_RUNNING,
            fn (JobStatus $status) => $status->withMetadata(['started_at' => Carbon::now()])
        );
    }

    public function markCompleted(string $jobId): JobStatus
    {
        return $this->updateStatus(
            $jobId,
            JobStatus::STATUS_COMPLETED,
            fn (JobStatus $status) => $status->withMetadata(['completed_at' => Carbon::now()])
        );
    }

    public function markFailed(string $jobId, string $error): JobStatus
    {
        return $this->updateStatus(
            $jobId,
            JobStatus::STATUS_FAILED,
            fn (JobStatus $status) => $status
                ->withError($error)
                ->withMetadata(['failed_at' => Carbon::now()])
        );
    }

    public function markRetrying(string $jobId, Carbon $nextRetry): JobStatus
    {
        return $this->updateStatus(
            $jobId,
            JobStatus::STATUS_RETRYING,
            fn (JobStatus $status) => $status
                ->withNextRetryAt($nextRetry)
                ->withAttempts($status->getAttempts() + 1)
                ->withMetadata(['last_retry_at' => Carbon::now()])
        );
    }

    public function updateProgress(string $jobId, int $progress): JobStatus
    {
        if ($progress < 0 || $progress > 100) {
            throw new RuntimeException('Progress must be between 0 and 100');
        }

        return $this->updateStatus(
            $jobId,
            null,
            fn (JobStatus $status) => $status
                ->withProgress($progress)
                ->withMetadata(['last_progress_update' => Carbon::now()])
        );
    }

    public function exists(string $jobId): bool
    {
        return Cache::has($this->getCacheKey($jobId));
    }

    public function remove(string $jobId): void
    {
        Cache::forget($this->getCacheKey($jobId));
    }

    public function clear(): void
    {
        // Note: This is a simplified implementation
        // In production, you might want to use a more sophisticated cleanup method
        Cache::tags($this->cachePrefix)->flush();
    }

    private function store(JobStatus $status): void
    {
        Cache::put(
            $this->getCacheKey($status->getJobId()),
            $status->toArray(),
            $this->ttl
        );
    }

    private function updateStatus(
        string $jobId,
        ?string $newStatus,
        callable $modifier
    ): JobStatus {
        $status = $this->get($jobId);

        if ($newStatus !== null) {
            $status = $status->withStatus($newStatus);
        }

        $status = $modifier($status);
        $this->store($status);

        return $status;
    }

    private function getCacheKey(string $jobId): string
    {
        return $this->cachePrefix.$jobId;
    }

    public function withTtl(int $ttl): self
    {
        $clone = clone $this;
        $clone->ttl = $ttl;

        return $clone;
    }

    public function withPrefix(string $prefix): self
    {
        $clone = clone $this;
        $clone->cachePrefix = $prefix;

        return $clone;
    }
}
