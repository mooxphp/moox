<?php

declare(strict_types=1);

namespace Moox\Cache\Targets;

use Illuminate\Support\Facades\Cache;
use Moox\Cache\Data\CacheClearRequest;
use Moox\Cache\Data\CacheClearResult;
use Moox\Cache\Support\AbstractCacheTarget;

class CustomKeyCacheTarget extends AbstractCacheTarget
{
    public function __construct()
    {
        parent::__construct(
            targetKey: 'custom-key',
            targetLabel: __('moox-cache::cache.targets.custom_key.label'),
            targetDescription: __('moox-cache::cache.targets.custom_key.description'),
            targetCategory: 'keys',
            targetIcon: 'heroicon-o-key',
            targetColor: 'warning',
        );
    }

    public static function make(): self
    {
        return new self;
    }

    public function clear(CacheClearRequest $request): CacheClearResult
    {
        $startedAt = microtime(true);

        if ($request->key === null || $request->key === '') {
            return new CacheClearResult(
                success: false,
                message: __('moox-cache::cache.messages.key_required'),
                durationMs: (microtime(true) - $startedAt) * 1000,
            );
        }

        $forgot = Cache::forget($request->key);
        $durationMs = (microtime(true) - $startedAt) * 1000;

        return new CacheClearResult(
            success: $forgot,
            message: $forgot
                ? __('moox-cache::cache.messages.key_forgotten', ['key' => $request->key])
                : __('moox-cache::cache.messages.key_not_found', ['key' => $request->key]),
            durationMs: $durationMs,
        );
    }
}
