<?php

declare(strict_types=1);

namespace Moox\Cloudflare;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;

class CloudflareCachePlugin implements Plugin
{
    use EvaluatesClosures;

    protected bool|Closure $canAccess = true;

    public function getId(): string
    {
        return 'moox-cloudflare';
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function canAccess(bool|Closure $condition): static
    {
        $this->canAccess = $condition;

        return $this;
    }

    public function canUserAccess(): bool
    {
        return (bool) $this->evaluate($this->canAccess);
    }
}
