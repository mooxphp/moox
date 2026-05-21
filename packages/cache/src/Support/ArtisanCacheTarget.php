<?php

declare(strict_types=1);

namespace Moox\Cache\Support;

use Illuminate\Support\Facades\Artisan;
use Moox\Cache\Data\CacheClearRequest;
use Moox\Cache\Data\CacheClearResult;

class ArtisanCacheTarget extends AbstractCacheTarget
{
    public function __construct(
        string $key,
        string $label,
        protected string $command,
        ?string $description = null,
        string $category = 'laravel',
        ?string $icon = 'heroicon-o-trash',
        ?string $color = 'danger',
    ) {
        parent::__construct($key, $label, $description, $category, $icon, $color);
    }

    public static function make(
        string $key,
        string $label,
        string $command,
        ?string $description = null,
        string $category = 'laravel',
        ?string $icon = 'heroicon-o-trash',
        ?string $color = 'danger',
    ): self {
        return new self($key, $label, $command, $description, $category, $icon, $color);
    }

    public function clear(CacheClearRequest $request): CacheClearResult
    {
        $startedAt = microtime(true);

        $exitCode = Artisan::call($this->command);
        $output = trim(Artisan::output());
        $durationMs = (microtime(true) - $startedAt) * 1000;

        $success = $exitCode === 0;

        return new CacheClearResult(
            success: $success,
            message: $success
                ? __('moox-cache::cache.messages.cleared', ['target' => $this->label()])
                : __('moox-cache::cache.messages.failed', ['target' => $this->label()]),
            output: $output !== '' ? $output : null,
            durationMs: $durationMs,
        );
    }
}
