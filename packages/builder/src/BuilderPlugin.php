<?php

namespace Moox\Builder;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;

class BuilderPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'builder';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            BuilderResource::class,
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
