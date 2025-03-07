<?php

declare(strict_types=1);

namespace Moox\Connect\Health;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Moox\Connect\Connect\ApiRequest;
use Moox\Connect\Connect\ApiResponse;

final class ApiHealthMonitor
{
    private string $cachePrefix;

    private int $errorThreshold;

    private int $timeWindow;

    private ApiErrorLogger $errorLogger;

    public function __construct(
        string $cachePrefix = 'api_health:',
        int $errorThreshold = 5,
        int $timeWindow = 300,
        ?ApiErrorLogger $errorLogger = null
    ) {
        $this->cachePrefix = $cachePrefix;
        $this->errorThreshold = $errorThreshold;
        $this->timeWindow = $timeWindow;
        $this->errorLogger = $errorLogger ?? new ApiErrorLogger;
    }

    public function recordSuccess(string $apiId, ApiRequest $request, ApiResponse $response): void
    {
        $this->clearErrors($apiId);
        $this->updateLastSuccess($apiId);
        $this->updateResponseTime($apiId, $response);
    }

    public function recordError(string $apiId, ApiRequest $request, ?ApiResponse $response, \Throwable $error): void
    {
        $this->errorLogger->logRequestError($request, $response, $error);
        $this->incrementErrors($apiId);

        if ($this->getErrorCount($apiId) >= $this->errorThreshold) {
            $this->markAsUnhealthy($apiId);
        }
    }

    public function isHealthy(string $apiId): bool
    {
        return ! Cache::has("{$this->cachePrefix}{$apiId}:unhealthy");
    }

    public function getStatus(string $apiId): array
    {
        return [
            'healthy' => $this->isHealthy($apiId),
            'error_count' => $this->getErrorCount($apiId),
            'last_success' => $this->getLastSuccess($apiId),
            'average_response_time' => $this->getAverageResponseTime($apiId),
        ];
    }

    private function incrementErrors(string $apiId): void
    {
        $key = "{$this->cachePrefix}{$apiId}:errors";
        $errors = Cache::get($key, []);

        // Add current timestamp to errors array
        $errors[] = Carbon::now()->timestamp;

        // Remove errors outside the time window
        $cutoff = Carbon::now()->subSeconds($this->timeWindow)->timestamp;
        $errors = array_filter($errors, fn ($timestamp) => $timestamp >= $cutoff);

        Cache::put($key, $errors, Carbon::now()->addSeconds($this->timeWindow));
    }

    private function getErrorCount(string $apiId): int
    {
        $errors = Cache::get("{$this->cachePrefix}{$apiId}:errors", []);
        $cutoff = Carbon::now()->subSeconds($this->timeWindow)->timestamp;

        return count(array_filter($errors, fn ($timestamp) => $timestamp >= $cutoff));
    }

    private function clearErrors(string $apiId): void
    {
        Cache::forget("{$this->cachePrefix}{$apiId}:errors");
    }

    private function markAsUnhealthy(string $apiId): void
    {
        Cache::put(
            "{$this->cachePrefix}{$apiId}:unhealthy",
            true,
            Carbon::now()->addSeconds($this->timeWindow)
        );
    }

    private function updateLastSuccess(string $apiId): void
    {
        Cache::put(
            "{$this->cachePrefix}{$apiId}:last_success",
            Carbon::now()->timestamp,
            Carbon::now()->addDay()
        );
    }

    private function getLastSuccess(string $apiId): ?int
    {
        return Cache::get("{$this->cachePrefix}{$apiId}:last_success");
    }

    private function updateResponseTime(string $apiId, ApiResponse $response): void
    {
        $key = "{$this->cachePrefix}{$apiId}:response_times";
        $times = Cache::get($key, []);

        // Add current response time
        $times[] = $this->getResponseTime($response);

        // Keep only last 100 response times
        $times = array_slice($times, -100);

        Cache::put($key, $times, Carbon::now()->addDay());
    }

    private function getAverageResponseTime(string $apiId): ?float
    {
        $times = Cache::get("{$this->cachePrefix}{$apiId}:response_times", []);
        if (empty($times)) {
            return null;
        }

        return array_sum($times) / count($times);
    }

    private function getResponseTime(ApiResponse $response): float
    {
        // Assuming response time is stored in a header
        $time = $response->getHeader('x-response-time');
        if ($time === null) {
            return 0.0;
        }

        return (float) $time;
    }
}
