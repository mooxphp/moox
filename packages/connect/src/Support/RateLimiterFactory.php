<?php

declare(strict_types=1);

namespace Moox\Connect\Support;

use Illuminate\Support\Facades\Config;

final class RateLimiterFactory
{
    public function forEndpoint(string $endpointId, ?int $customLimit = null, ?int $customWindow = null): RateLimiter
    {
        $config = Config::get('connect.rate_limits');

        $limit = $customLimit
            ?? $config['per_endpoint']['default']['max_requests']
            ?? $config['global']['max_requests'];

        $window = $customWindow
            ?? $config['per_endpoint']['default']['window']
            ?? $config['global']['window'];

        return new RateLimiter(
            cachePrefix: "connect:endpoint:{$endpointId}:",
            maxRequests: $limit,
            timeWindow: $window
        );
    }

    public function forJob(string $jobId): RateLimiter
    {
        $config = Config::get('connect.rate_limits');

        return new RateLimiter(
            cachePrefix: "connect:job:{$jobId}:",
            maxRequests: $config['per_job']['default']['max_requests'],
            timeWindow: $config['per_job']['default']['window']
        );
    }
}
