<?php

declare(strict_types=1);

namespace Moox\Connect\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Moox\Connect\DataObjects\RateLimitResult;

final class RateLimiter
{
    private string $cachePrefix;

    private int $maxRequests;

    private int $timeWindow;

    private bool $distributed;

    public function __construct(
        string $cachePrefix = 'rate_limit:',
        int $maxRequests = 60,
        int $timeWindow = 60,
        bool $distributed = true
    ) {
        $this->cachePrefix = $cachePrefix;
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
        $this->distributed = $distributed;
    }

    public function attempt(string $key = 'default'): RateLimitResult
    {
        $cacheKey = "{$this->cachePrefix}{$key}";

        return $this->distributed
            ? $this->distributedAttempt($cacheKey)
            : $this->localAttempt($cacheKey);
    }

    private function distributedAttempt(string $cacheKey): RateLimitResult
    {
        $now = Carbon::now()->timestamp;
        $window = intdiv($now, $this->timeWindow) * $this->timeWindow;
        $windowKey = "{$cacheKey}:{$window}";

        $requestCount = Cache::increment($windowKey);

        if ($requestCount === 1) {
            Cache::put($windowKey, 1, $this->timeWindow);
        }

        $remaining = max(0, $this->maxRequests - $requestCount);
        $reset = Carbon::createFromTimestamp($window + $this->timeWindow);

        return new RateLimitResult(
            allowed: $requestCount <= $this->maxRequests,
            limit: $this->maxRequests,
            remaining: $remaining,
            reset: $reset
        );
    }

    private function localAttempt(string $cacheKey): RateLimitResult
    {
        $now = Carbon::now()->timestamp;
        $requests = Cache::get($cacheKey, []);

        $requests = array_filter(
            $requests,
            fn ($timestamp) => $timestamp > $now - $this->timeWindow
        );

        $remaining = max(0, $this->maxRequests - count($requests));
        $reset = count($requests) > 0
            ? Carbon::createFromTimestamp(min($requests) + $this->timeWindow)
            : Carbon::now()->addSeconds($this->timeWindow);

        if (count($requests) < $this->maxRequests) {
            $requests[] = $now;
            Cache::put($cacheKey, $requests, $this->timeWindow);
        }

        return new RateLimitResult(
            allowed: count($requests) <= $this->maxRequests,
            limit: $this->maxRequests,
            remaining: $remaining,
            reset: $reset
        );
    }

    public function getRemainingRequests(string $key = 'default'): int
    {
        $cacheKey = "{$this->cachePrefix}{$key}";
        $now = Carbon::now()->timestamp;

        if ($this->distributed) {
            $window = intdiv($now, $this->timeWindow) * $this->timeWindow;
            $windowKey = "{$cacheKey}:{$window}";
            $count = (int) Cache::get($windowKey, 0);
        } else {
            $requests = Cache::get($cacheKey, []);
            $count = count(array_filter(
                $requests,
                fn ($timestamp) => $timestamp > $now - $this->timeWindow
            ));
        }

        return max(0, $this->maxRequests - $count);
    }

    public function getResetTime(string $key = 'default'): int
    {
        $now = Carbon::now()->timestamp;
        $window = intdiv($now, $this->timeWindow) * $this->timeWindow;

        return (int) ($window + $this->timeWindow - $now);
    }

    public function withMaxRequests(int $maxRequests): self
    {
        $clone = clone $this;
        $clone->maxRequests = $maxRequests;

        return $clone;
    }

    public function withTimeWindow(int $timeWindow): self
    {
        $clone = clone $this;
        $clone->timeWindow = $timeWindow;

        return $clone;
    }

    public function throttle(): void
    {
        $this->attempt();
    }
}
