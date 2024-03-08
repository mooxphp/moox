<?php

namespace Moox\Press;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Press\Resources\PressResource;

class PressPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'audit';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            PressResource::class,
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
