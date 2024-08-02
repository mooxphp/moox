<?php

namespace Moox\Sync;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Sync\Resources\SyncResource;

class SyncPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'sync';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            SyncResource::class,
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

    public static function getNavigationSort(): ?int
    {
        return config('sync.navigation_sort');
    }
}
