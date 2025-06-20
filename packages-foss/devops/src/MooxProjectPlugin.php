<?php

namespace Moox\Devops;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Devops\Resources\MooxProjectResource;

class MooxProjectPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'forge-projects';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            MooxProjectResource::class,
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
