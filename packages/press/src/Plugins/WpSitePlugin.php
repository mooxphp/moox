<?php

declare(strict_types=1);

namespace Moox\Press\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Press\Resources\WpSiteResource;

class WpSitePlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'wp-site';
    }

    public function register(Panel $panel): void
    {
        if (config('press.multisite') !== true) {
            return;
        }

        $panel->resources([
            WpSiteResource::class,
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
