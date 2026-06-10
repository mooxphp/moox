<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Filament\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\DataLegacy\Filament\Resources\StaticLocaleResource;

class StaticLocalePlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'staticlocale';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            StaticLocaleResource::class,
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
