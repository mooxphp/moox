<?php

namespace Moox\Locate;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Locate\Resources\LocateResource;

class LocatePlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'locate';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            LocateResource::class,
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
