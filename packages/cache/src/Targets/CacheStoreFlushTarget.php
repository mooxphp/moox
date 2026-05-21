<?php

declare(strict_types=1);

namespace Moox\Cache\Targets;

use Illuminate\Support\Facades\Cache;
use Moox\Cache\Data\CacheClearRequest;
use Moox\Cache\Data\CacheClearResult;
use Moox\Cache\Support\AbstractCacheTarget;

class CacheStoreFlushTarget extends AbstractCacheTarget
{
    public function __construct()
    {
        parent::__construct(
            targetKey: 'cache-store-flush',
            targetLabel: __('moox-cache::cache.targets.cache_store_flush.label'),
            targetDescription: __('moox-cache::cache.targets.cache_store_flush.description'),
            targetCategory: 'stores',
            targetIcon: 'heroicon-o-server-stack',
            targetColor: 'danger',
        );
    }

    public static function make(): self
    {
        return new self;
    }

    public function clear(CacheClearRequest $request): CacheClearResult
    {
        $startedAt = microtime(true);
        $store = $request->store ?? config('cache.default');

        try {
            Cache::store($store)->flush();
            $success = true;
            $message = __('moox-cache::cache.messages.store_flushed', ['store' => $store]);
        } catch (\Throwable $exception) {
            $success = false;
            $message = $exception->getMessage();
        }

        return new CacheClearResult(
            success: $success,
            message: $message,
            durationMs: (microtime(true) - $startedAt) * 1000,
        );
    }
}
