<?php

namespace Moox\Sync;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Sync\Resources\PlatformResource;

class PlatformPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'platform';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            PlatformResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
