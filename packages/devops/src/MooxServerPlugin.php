<?php

namespace Moox\Devops;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Devops\Resources\MooxServerResource;

class MooxServerPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'devops';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            MooxServerResource::class,
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
