<?php

declare(strict_types=1);

namespace Moox\Cloudflare\Support;

use Moox\Cache\Data\CacheClearRequest;
use Moox\Cache\Data\CacheClearResult;
use Moox\Cache\Enums\CacheTargetStatus;
use Moox\Cache\Support\AbstractCacheTarget;
use Moox\Cloudflare\CloudflareClient;

abstract class AbstractCloudflareTarget extends AbstractCacheTarget
{
    public function __construct(
        string $key,
        string $label,
        ?string $description = null,
        ?string $icon = 'heroicon-o-cloud',
        ?string $color = 'info',
    ) {
        parent::__construct(
            targetKey: $key,
            targetLabel: $label,
            targetDescription: $description,
            targetCategory: 'cloudflare',
            targetIcon: $icon,
            targetColor: $color,
        );
    }

    public function status(): CacheTargetStatus
    {
        return app(CloudflareClient::class)->isConfigured()
            ? CacheTargetStatus::Available
            : CacheTargetStatus::Unavailable;
    }

    /**
     * @return array{success: bool, message: string, result: mixed}
     */
    abstract protected function purge(CloudflareClient $client, CacheClearRequest $request): array;

    public function clear(CacheClearRequest $request): CacheClearResult
    {
        $startedAt = microtime(true);
        $response = $this->purge(app(CloudflareClient::class), $request);

        return new CacheClearResult(
            success: $response['success'],
            message: $response['message'],
            output: is_array($response['result']) ? json_encode($response['result'], JSON_PRETTY_PRINT) : null,
            durationMs: (microtime(true) - $startedAt) * 1000,
        );
    }
}
