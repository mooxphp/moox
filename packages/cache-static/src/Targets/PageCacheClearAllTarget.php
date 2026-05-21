<?php

declare(strict_types=1);

namespace Moox\CacheStatic\Targets;

use Illuminate\Support\Facades\Artisan;
use Moox\Cache\Data\CacheClearRequest;
use Moox\Cache\Data\CacheClearResult;
use Moox\Cache\Enums\CacheTargetStatus;
use Moox\Cache\Support\AbstractCacheTarget;
class PageCacheClearAllTarget extends AbstractCacheTarget
{
    public function __construct()
    {
        parent::__construct(
            targetKey: 'page-cache-clear-all',
            targetLabel: __('moox-cache-static::cache-static.targets.clear_all.label'),
            targetDescription: __('moox-cache-static::cache-static.targets.clear_all.description'),
            targetCategory: 'page-cache',
            targetIcon: 'heroicon-o-document-text',
            targetColor: 'danger',
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

        $exitCode = Artisan::call($command);
        $output = trim(Artisan::output());

        return new CacheClearResult(
            success: $exitCode === 0,
            message: $exitCode === 0
                ? __('moox-cache-static::cache-static.messages.cleared_all')
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
