<?php

declare(strict_types=1);

namespace Moox\Connect\Support;

use Moox\Connect\Models\ApiConnection;
use Moox\Connect\Models\ApiEndpoint;

final class ConnectQueueSettingsResolver
{
    /**
     * @var array<string, ConnectQueueSettings>
     */
    private static array $cache = [];

    public static function clearCache(): void
    {
        self::$cache = [];
    }

    public function resolve(string $jobType, ?int $endpointId = null, ?int $connectionId = null): ConnectQueueSettings
    {
        $cacheKey = $jobType.':'.($endpointId ?? 'null').':'.($connectionId ?? 'null');

        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $endpoint = $endpointId !== null
            ? ApiEndpoint::query()->with('apiConnection')->find($endpointId)
            : null;

        $connection = $endpoint?->apiConnection
            ?? ($connectionId !== null ? ApiConnection::query()->find($connectionId) : null);

        if ($connectionId === null && $connection !== null) {
            $connectionId = (int) $connection->id;
        }

        $settings = new ConnectQueueSettings(
            queue: $this->resolveQueue($jobType, $endpoint, $connection, $connectionId, $endpointId),
            tries: $this->resolveTries($jobType, $endpoint, $connection, $connectionId, $endpointId),
            timeout: $this->resolveTimeout($jobType, $endpoint, $connection, $connectionId, $endpointId),
            maxExceptions: $this->resolveMaxExceptions($jobType, $endpoint, $connection, $connectionId, $endpointId),
            backoff: $this->resolveBackoff($jobType, $endpoint, $connection, $connectionId, $endpointId),
            retryUntilMinutes: $this->resolveRetryUntilMinutes($jobType, $endpoint, $connection, $connectionId, $endpointId),
            overlapReleaseAfter: $this->resolveOverlapReleaseAfter($jobType, $endpoint, $connection, $connectionId, $endpointId),
            overlapExpireBuffer: $this->resolveOverlapExpireBuffer($jobType, $endpoint, $connection, $connectionId, $endpointId),
            overlapExpireMin: $this->resolveOverlapExpireMin($jobType, $endpoint, $connection, $connectionId, $endpointId),
            deadlockRetryAttempts: $this->resolveDeadlockRetryAttempts($jobType, $endpoint, $connection, $connectionId, $endpointId),
            deadlockRetryDelaysMs: $this->resolveDeadlockRetryDelaysMs($jobType, $endpoint, $connection, $connectionId, $endpointId),
        );

        return self::$cache[$cacheKey] = $settings;
    }

    private function resolveQueue(
        string $jobType,
        ?ApiEndpoint $endpoint,
        ?ApiConnection $connection,
        ?int $connectionId,
        ?int $endpointId,
    ): string {
        return $this->firstString([
            $this->optionValue($endpoint?->options, 'queue'),
            $this->optionValue($endpoint?->options, 'queue.name'),
            $this->configMapValue('connect.queues.endpoints', $endpointId, $endpoint?->name, 'queue'),
            $this->optionValue($connection?->options, 'queue'),
            $this->optionValue($connection?->options, 'queue.name'),
            $this->configMapValue('connect.queues.connections', $connectionId, $connection?->name, 'queue'),
            config("connect.jobs.{$jobType}.queue"),
            config('connect.queues.default', 'default'),
        ]);
    }

    private function resolveTries(
        string $jobType,
        ?ApiEndpoint $endpoint,
        ?ApiConnection $connection,
        ?int $connectionId,
        ?int $endpointId,
    ): int {
        return $this->firstPositiveInt([
            $this->optionValue($endpoint?->options, 'queue.tries'),
            $this->configMapValue('connect.queues.endpoints', $endpointId, $endpoint?->name, 'tries'),
            $this->optionValue($connection?->options, 'queue.tries'),
            $this->configMapValue('connect.queues.connections', $connectionId, $connection?->name, 'tries'),
            config("connect.jobs.{$jobType}.tries"),
            config('connect.queues.worker_tries', 5),
        ], 5);
    }

    private function resolveTimeout(
        string $jobType,
        ?ApiEndpoint $endpoint,
        ?ApiConnection $connection,
        ?int $connectionId,
        ?int $endpointId,
    ): int {
        return $this->firstPositiveInt([
            $this->optionValue($endpoint?->options, 'queue.timeout'),
            $this->configMapValue('connect.queues.endpoints', $endpointId, $endpoint?->name, 'timeout'),
            $this->optionValue($connection?->options, 'queue.timeout'),
            $this->configMapValue('connect.queues.connections', $connectionId, $connection?->name, 'timeout'),
            config("connect.jobs.{$jobType}.timeout"),
            config('connect.queues.worker_timeout', 180),
        ], 180);
    }

    private function resolveMaxExceptions(
        string $jobType,
        ?ApiEndpoint $endpoint,
        ?ApiConnection $connection,
        ?int $connectionId,
        ?int $endpointId,
    ): int {
        return $this->firstPositiveInt([
            $this->optionValue($endpoint?->options, 'queue.max_exceptions'),
            $this->configMapValue('connect.queues.endpoints', $endpointId, $endpoint?->name, 'max_exceptions'),
            $this->optionValue($connection?->options, 'queue.max_exceptions'),
            $this->configMapValue('connect.queues.connections', $connectionId, $connection?->name, 'max_exceptions'),
            config("connect.jobs.{$jobType}.max_exceptions"),
        ], 5);
    }

    /**
     * @return array<int, int>
     */
    private function resolveBackoff(
        string $jobType,
        ?ApiEndpoint $endpoint,
        ?ApiConnection $connection,
        ?int $connectionId,
        ?int $endpointId,
    ): array {
        return $this->firstIntArray([
            $this->optionValue($endpoint?->options, 'queue.backoff'),
            $this->configMapValue('connect.queues.endpoints', $endpointId, $endpoint?->name, 'backoff'),
            $this->optionValue($connection?->options, 'queue.backoff'),
            $this->configMapValue('connect.queues.connections', $connectionId, $connection?->name, 'backoff'),
            config("connect.jobs.{$jobType}.backoff"),
        ], [30, 120, 300]);
    }

    private function resolveRetryUntilMinutes(
        string $jobType,
        ?ApiEndpoint $endpoint,
        ?ApiConnection $connection,
        ?int $connectionId,
        ?int $endpointId,
    ): int {
        return $this->firstNonNegativeInt([
            $this->optionValue($endpoint?->options, 'queue.retry_until_minutes'),
            $this->configMapValue('connect.queues.endpoints', $endpointId, $endpoint?->name, 'retry_until_minutes'),
            $this->optionValue($connection?->options, 'queue.retry_until_minutes'),
            $this->configMapValue('connect.queues.connections', $connectionId, $connection?->name, 'retry_until_minutes'),
            config("connect.jobs.{$jobType}.retry_until_minutes"),
        ], 480);
    }

    private function resolveOverlapReleaseAfter(
        string $jobType,
        ?ApiEndpoint $endpoint,
        ?ApiConnection $connection,
        ?int $connectionId,
        ?int $endpointId,
    ): int {
        return $this->firstPositiveInt([
            $this->optionValue($endpoint?->options, 'queue.overlap.release_after'),
            $this->configMapValue('connect.queues.endpoints', $endpointId, $endpoint?->name, 'overlap.release_after'),
            $this->optionValue($connection?->options, 'queue.overlap.release_after'),
            $this->configMapValue('connect.queues.connections', $connectionId, $connection?->name, 'overlap.release_after'),
            config("connect.jobs.{$jobType}.overlap.release_after"),
        ], 15);
    }

    private function resolveOverlapExpireBuffer(
        string $jobType,
        ?ApiEndpoint $endpoint,
        ?ApiConnection $connection,
        ?int $connectionId,
        ?int $endpointId,
    ): int {
        return $this->firstNonNegativeInt([
            $this->optionValue($endpoint?->options, 'queue.overlap.expire_buffer'),
            $this->configMapValue('connect.queues.endpoints', $endpointId, $endpoint?->name, 'overlap.expire_buffer'),
            $this->optionValue($connection?->options, 'queue.overlap.expire_buffer'),
            $this->configMapValue('connect.queues.connections', $connectionId, $connection?->name, 'overlap.expire_buffer'),
            config("connect.jobs.{$jobType}.overlap.expire_buffer"),
        ], 60);
    }

    private function resolveOverlapExpireMin(
        string $jobType,
        ?ApiEndpoint $endpoint,
        ?ApiConnection $connection,
        ?int $connectionId,
        ?int $endpointId,
    ): int {
        return $this->firstNonNegativeInt([
            $this->optionValue($endpoint?->options, 'queue.overlap.expire_min'),
            $this->configMapValue('connect.queues.endpoints', $endpointId, $endpoint?->name, 'overlap.expire_min'),
            $this->optionValue($connection?->options, 'queue.overlap.expire_min'),
            $this->configMapValue('connect.queues.connections', $connectionId, $connection?->name, 'overlap.expire_min'),
            config("connect.jobs.{$jobType}.overlap.expire_min"),
        ], 300);
    }

    private function resolveDeadlockRetryAttempts(
        string $jobType,
        ?ApiEndpoint $endpoint,
        ?ApiConnection $connection,
        ?int $connectionId,
        ?int $endpointId,
    ): int {
        return $this->firstPositiveInt([
            $this->optionValue($endpoint?->options, 'queue.deadlock_retry.attempts'),
            $this->configMapValue('connect.queues.endpoints', $endpointId, $endpoint?->name, 'deadlock_retry.attempts'),
            $this->optionValue($connection?->options, 'queue.deadlock_retry.attempts'),
            $this->configMapValue('connect.queues.connections', $connectionId, $connection?->name, 'deadlock_retry.attempts'),
            config("connect.jobs.{$jobType}.deadlock_retry.attempts"),
        ], 3);
    }

    /**
     * @return array<int, int>
     */
    private function resolveDeadlockRetryDelaysMs(
        string $jobType,
        ?ApiEndpoint $endpoint,
        ?ApiConnection $connection,
        ?int $connectionId,
        ?int $endpointId,
    ): array {
        return $this->firstIntArray([
            $this->optionValue($endpoint?->options, 'queue.deadlock_retry.delays_ms'),
            $this->configMapValue('connect.queues.endpoints', $endpointId, $endpoint?->name, 'deadlock_retry.delays_ms'),
            $this->optionValue($connection?->options, 'queue.deadlock_retry.delays_ms'),
            $this->configMapValue('connect.queues.connections', $connectionId, $connection?->name, 'deadlock_retry.delays_ms'),
            config("connect.jobs.{$jobType}.deadlock_retry.delays_ms"),
        ], [100, 250]);
    }

    /**
     * @param  array<string, mixed>|null  $options
     */
    private function optionValue(?array $options, string $key): mixed
    {
        if ($options === null) {
            return null;
        }

        if (array_key_exists($key, $options)) {
            return $options[$key];
        }

        return data_get($options, $key);
    }

    /**
     * @return array<int, mixed>
     */
    private function configMapValue(string $configKey, ?int $id, ?string $name, string $field): mixed
    {
        $map = config($configKey, []);

        if (! is_array($map)) {
            return null;
        }

        if ($id !== null) {
            $byId = $map[(string) $id] ?? $map[$id] ?? null;
            if (is_array($byId)) {
                return data_get($byId, $field);
            }
        }

        if (is_string($name) && $name !== '') {
            $byName = $map[$name] ?? null;
            if (is_array($byName)) {
                return data_get($byName, $field);
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $candidates
     */
    private function firstString(array $candidates): string
    {
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return 'default';
    }

    /**
     * @param  array<int, mixed>  $candidates
     */
    private function firstPositiveInt(array $candidates, int $fallback): int
    {
        foreach ($candidates as $candidate) {
            if (is_numeric($candidate) && (int) $candidate > 0) {
                return (int) $candidate;
            }
        }

        return $fallback;
    }

    /**
     * @param  array<int, mixed>  $candidates
     */
    private function firstNonNegativeInt(array $candidates, int $fallback): int
    {
        foreach ($candidates as $candidate) {
            if (is_numeric($candidate) && (int) $candidate >= 0) {
                return (int) $candidate;
            }
        }

        return $fallback;
    }

    /**
     * @param  array<int, mixed>  $candidates
     * @return array<int, int>
     */
    private function firstIntArray(array $candidates, array $fallback): array
    {
        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeIntArray($candidate);

            if ($normalized !== []) {
                return $normalized;
            }
        }

        return $fallback;
    }

    /**
     * @return array<int, int>
     */
    private function normalizeIntArray(mixed $value): array
    {
        if (is_string($value)) {
            $value = array_map('trim', explode(',', $value));
        }

        if (! is_array($value)) {
            return [];
        }

        $ints = [];

        foreach ($value as $item) {
            if (is_numeric($item) && (int) $item >= 0) {
                $ints[] = (int) $item;
            }
        }

        return $ints;
    }
}
