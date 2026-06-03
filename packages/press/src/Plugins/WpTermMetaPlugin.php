<?php

declare(strict_types=1);

namespace Moox\Press\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Press\Resources\WpTermMetaResource;

class WpTermMetaPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'wp-termmeta';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            WpTermMetaResource::class,
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
