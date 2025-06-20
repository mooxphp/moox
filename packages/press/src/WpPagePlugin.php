<?php

namespace Moox\Press;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Press\Resources\WpPageResource;

class WpPagePlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'wp-page';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            WpPageResource::class,
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
