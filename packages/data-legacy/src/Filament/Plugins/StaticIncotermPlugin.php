<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Filament\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\DataLegacy\Filament\Resources\StaticIncotermResource;

class StaticIncotermPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'staticincoterm';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            StaticIncotermResource::class,
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
