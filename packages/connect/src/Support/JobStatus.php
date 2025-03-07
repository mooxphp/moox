<?php

declare(strict_types=1);

namespace Moox\Connect\Support;

use Carbon\Carbon;
use RuntimeException;

final class JobStatus
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_RETRYING = 'retrying';

    public const STATUS_CANCELLED = 'cancelled';

    private string $jobId;

    private string $status;

    private ?string $error;

    private int $attempts;

    private ?int $progress;

    private ?Carbon $startedAt;

    private ?Carbon $completedAt;

    private ?Carbon $nextRetryAt;

    private array $metadata;

    public function __construct(
        string $jobId,
        string $status = self::STATUS_PENDING,
        ?string $error = null,
        int $attempts = 0,
        ?int $progress = null,
        ?Carbon $startedAt = null,
        ?Carbon $completedAt = null,
        ?Carbon $nextRetryAt = null,
        array $metadata = []
    ) {
        $this->validateStatus($status);

        $this->jobId = $jobId;
        $this->status = $status;
        $this->error = $error;
        $this->attempts = $attempts;
        $this->progress = $progress;
        $this->startedAt = $startedAt;
        $this->completedAt = $completedAt;
        $this->nextRetryAt = $nextRetryAt;
        $this->metadata = $metadata;
    }

    public function toArray(): array
    {
        return [
            'job_id' => $this->jobId,
            'status' => $this->status,
            'error' => $this->error,
            'attempts' => $this->attempts,
            'progress' => $this->progress,
            'started_at' => $this->startedAt?->toIso8601String(),
            'completed_at' => $this->completedAt?->toIso8601String(),
            'next_retry_at' => $this->nextRetryAt?->toIso8601String(),
            'metadata' => $this->metadata,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            jobId: $data['job_id'],
            status: $data['status'],
            error: $data['error'] ?? null,
            attempts: $data['attempts'] ?? 0,
            progress: $data['progress'] ?? null,
            startedAt: isset($data['started_at']) ? Carbon::parse($data['started_at']) : null,
            completedAt: isset($data['completed_at']) ? Carbon::parse($data['completed_at']) : null,
            nextRetryAt: isset($data['next_retry_at']) ? Carbon::parse($data['next_retry_at']) : null,
            metadata: $data['metadata'] ?? []
        );
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
        ], true);
    }

    public function withStatus(string $status): self
    {
        $this->validateStatus($status);
        $clone = clone $this;
        $clone->status = $status;

        return $clone;
    }

    public function withError(?string $error): self
    {
        $clone = clone $this;
        $clone->error = $error;

        return $clone;
    }

    public function withProgress(?int $progress): self
    {
        if ($progress !== null && ($progress < 0 || $progress > 100)) {
            throw new RuntimeException('Progress must be between 0 and 100');
        }

        $clone = clone $this;
        $clone->progress = $progress;

        return $clone;
    }

    public function withAttempts(int $attempts): self
    {
        if ($attempts < 0) {
            throw new RuntimeException('Attempts cannot be negative');
        }

        $clone = clone $this;
        $clone->attempts = $attempts;

        return $clone;
    }

    public function withNextRetryAt(?Carbon $nextRetryAt): self
    {
        $clone = clone $this;
        $clone->nextRetryAt = $nextRetryAt;

        return $clone;
    }

    public function withMetadata(array $metadata): self
    {
        $clone = clone $this;
        $clone->metadata = array_merge($this->metadata, $metadata);

        return $clone;
    }

    private function validateStatus(string $status): void
    {
        $validStatuses = [
            self::STATUS_PENDING,
            self::STATUS_RUNNING,
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_RETRYING,
            self::STATUS_CANCELLED,
        ];

        if (! in_array($status, $validStatuses, true)) {
            throw new RuntimeException("Invalid status: {$status}");
        }
    }

    // Getters
    public function getJobId(): string
    {
        return $this->jobId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function getProgress(): ?int
    {
        return $this->progress;
    }

    public function getStartedAt(): ?Carbon
    {
        return $this->startedAt;
    }

    public function getCompletedAt(): ?Carbon
    {
        return $this->completedAt;
    }

    public function getNextRetryAt(): ?Carbon
    {
        return $this->nextRetryAt;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    public function isRetrying(): bool
    {
        return $this->status === self::STATUS_RETRYING;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
