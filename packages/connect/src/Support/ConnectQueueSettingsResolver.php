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
        return $this->firstInt([
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
        return $this->firstInt([
            $this->optionValue($endpoint?->options, 'queue.timeout'),
            $this->configMapValue('connect.queues.endpoints', $endpointId, $endpoint?->name, 'timeout'),
            $this->optionValue($connection?->options, 'queue.timeout'),
            $this->configMapValue('connect.queues.connections', $connectionId, $connection?->name, 'timeout'),
            config("connect.jobs.{$jobType}.timeout"),
            config('connect.queues.worker_timeout', 180),
        ], 180);
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
                return $byId[$field] ?? null;
            }
        }

        if (is_string($name) && $name !== '') {
            $byName = $map[$name] ?? null;
            if (is_array($byName)) {
                return $byName[$field] ?? null;
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
    private function firstInt(array $candidates, int $fallback): int
    {
        foreach ($candidates as $candidate) {
            if (is_numeric($candidate) && (int) $candidate > 0) {
                return (int) $candidate;
            }
        }

        return $fallback;
    }
}
