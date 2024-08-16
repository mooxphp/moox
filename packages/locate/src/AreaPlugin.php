<?php

namespace Moox\Locate;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Locate\Resources\AreaResource;

class AreaPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'area';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            AreaResource::class,
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
