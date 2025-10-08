<?php

namespace Moox\Press;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Press\Resources\WpTagResource;

class WpTagPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'wp-tag';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            WpTagResource::class,
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
