<?php

declare(strict_types=1);

namespace Moox\CacheStatic\Targets;

use Illuminate\Support\Facades\Artisan;
use Moox\Cache\Data\CacheClearRequest;
use Moox\Cache\Data\CacheClearResult;
use Moox\Cache\Enums\CacheTargetStatus;
use Moox\Cache\Support\AbstractCacheTarget;

class PageCacheClearSlugTarget extends AbstractCacheTarget
{
    public function __construct()
    {
        parent::__construct(
            targetKey: 'page-cache-clear-slug',
            targetLabel: __('moox-cache-static::cache-static.targets.clear_slug.label'),
            targetDescription: __('moox-cache-static::cache-static.targets.clear_slug.description'),
            targetCategory: 'page-cache',
            targetIcon: 'heroicon-o-link',
            targetColor: 'warning',
        );
    }

    public static function make(): self
    {
        return new self;
    }

    public function status(): CacheTargetStatus
    {
        return $this->commandExists()
            ? CacheTargetStatus::Available
            : CacheTargetStatus::Unavailable;
    }

    public function clear(CacheClearRequest $request): CacheClearResult
    {
        $startedAt = microtime(true);
        $command = (string) config('cache-static.command', 'page-cache:clear');

        if (! $this->commandExists()) {
            return new CacheClearResult(
                success: false,
                message: __('moox-cache-static::cache-static.messages.command_missing', ['command' => $command]),
                durationMs: (microtime(true) - $startedAt) * 1000,
            );
        }

        if ($request->slug === null || $request->slug === '') {
            return new CacheClearResult(
                success: false,
                message: __('moox-cache-static::cache-static.messages.slug_required'),
                durationMs: (microtime(true) - $startedAt) * 1000,
            );
        }

        $parameters = ['slug' => $request->slug];

        if ($request->recursive) {
            $parameters['--recursive'] = true;
        }

        $exitCode = Artisan::call($command, $parameters);
        $output = trim(Artisan::output());

        return new CacheClearResult(
            success: $exitCode === 0,
            message: $exitCode === 0
                ? __('moox-cache-static::cache-static.messages.cleared_slug', ['slug' => $request->slug])
                : __('moox-cache-static::cache-static.messages.failed'),
            output: $output !== '' ? $output : null,
            durationMs: (microtime(true) - $startedAt) * 1000,
        );
    }

    protected function commandExists(): bool
    {
        return array_key_exists(
            (string) config('cache-static.command', 'page-cache:clear'),
            app(\Illuminate\Contracts\Console\Kernel::class)->all(),
        );
    }
}
