<?php

declare(strict_types=1);

namespace Moox\Connect\Jobs;

use Illuminate\Support\Collection;
use RuntimeException;

final class ChainedSyncJob extends BaseSyncJob
{
    private SingleEndpointSyncJob $sourceJob;

    private SingleEndpointSyncJob $targetJob;

    private array $parameterMapping;

    private ?array $sourceData = null;

    private bool $batchTarget;

    private ?int $batchSize;

    private ?string $batchMode;

    private ?int $batchDelay;

    public function __construct(
        SingleEndpointSyncJob $sourceJob,
        SingleEndpointSyncJob $targetJob,
        array $parameterMapping,
        bool $batchTarget = false,
        ?int $batchSize = null,
        ?string $batchMode = null,
        ?int $batchDelay = null,
        ...$args
    ) {
        parent::__construct(...$args);

        $this->sourceJob = $sourceJob;
        $this->targetJob = $targetJob;
        $this->parameterMapping = $parameterMapping;
        $this->batchTarget = $batchTarget;
        $this->batchSize = $batchSize;
        $this->batchMode = $batchMode;
        $this->batchDelay = $batchDelay;
    }

    protected function execute(): void
    {
        $this->executeSourceJob();

        if ($this->sourceData === null) {
            throw new RuntimeException('Source job did not provide any data');
        }

        $parameters = $this->resolveParameters();

        if ($this->batchTarget && ! empty($parameters)) {
            $this->executeBatchTargetJob($parameters);
        } else {
            $this->executeTargetJob($parameters);
        }
    }

    private function executeSourceJob(): void
    {
        $job = clone $this->sourceJob;

        $job->extend(function (array $data) {
            $this->sourceData = $data;
        });

        $job->handle();
    }

    private function resolveParameters(): array
    {
        $resolved = [];

        foreach ($this->parameterMapping as $mapping) {
            $sourceField = $mapping['source_field'];
            $targetParam = $mapping['target_param'];

            $values = $this->extractValues($this->sourceData, $sourceField);

            if ($this->batchTarget) {
                foreach ($values as $value) {
                    $resolved[] = [$targetParam => $value];
                }
            } else {
                $resolved[$targetParam] = $values[0] ?? null;
            }
        }

        return $resolved;
    }

    private function extractValues(array $data, string $path): array
    {
        $segments = explode('.', $path);
        $current = $data;

        foreach ($segments as $segment) {
            if ($segment === '[]') {
                if (! is_array($current)) {
                    throw new RuntimeException("Expected array at path segment: {$segment}");
                }

                return Collection::make($current)
                    ->flatten()
                    ->filter()
                    ->values()
                    ->all();
            }

            $current = $current[$segment] ?? null;

            if ($current === null) {
                return [];
            }
        }

        return is_array($current) ? $current : [$current];
    }

    private function executeTargetJob(array $parameters): void
    {
        $job = $this->targetJob->withParameters($parameters);
        $job->handle();
    }

    private function executeBatchTargetJob(array $parameterSets): void
    {
        $batchJob = new BatchSyncJob(
            $this->targetJob,
            $parameterSets,
            $this->batchMode ?? 'sequential',
            $this->batchSize ?? 10,
            $this->batchDelay,
            $this->jobId.'-batch',
            $this->apiId,
            $this->client,
            $this->statusManager
        );

        $batchJob->handle();
    }

    public function withBatchSettings(
        int $size,
        string $mode = 'sequential',
        ?int $delay = null
    ): self {
        $clone = clone $this;
        $clone->batchTarget = true;
        $clone->batchSize = $size;
        $clone->batchMode = $mode;
        $clone->batchDelay = $delay;

        return $clone;
    }
}
