<?php

namespace Moox\Press\Services;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Illuminate\Support\Facades\RateLimiter;

trait WithRateLimiting
{
    protected function clearRateLimiter($method = null, $component = null)
    {
        $method ??= debug_backtrace(limit: 2)[1]['function'];

        $component ??= static::class;

        $key = $this->getRateLimitKey($method, $component);

        RateLimiter::clear($key);
    }

    protected function getRateLimitKey($method, $component = null)
    {
        $method ??= debug_backtrace(limit: 2)[1]['function'];

        $component ??= static::class;

        $identifier = session()->getId().request()->ip();

        return sha1($component.'|'.$method.'|'.$identifier);
    }

    protected function hitRateLimiter($method = null, $decaySeconds = 60, $component = null)
    {
        $method ??= debug_backtrace(limit: 2)[1]['function'];

        $component ??= static::class;

        $key = $this->getRateLimitKey($method, $component);

        RateLimiter::hit($key, $decaySeconds);
    }

    protected function rateLimit($maxAttempts, $decaySeconds = 60, $method = null, $component = null)
    {
        $method ??= debug_backtrace(limit: 2)[1]['function'];

        $component ??= static::class;

        $key = $this->getRateLimitKey($method, $component);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $ip = request()->ip();
            $secondsUntilAvailable = RateLimiter::availableIn($key);

            throw new TooManyRequestsException($component, $method, $ip, $secondsUntilAvailable);
        }

        $this->hitRateLimiter($method, $decaySeconds, $component);
    }
}
