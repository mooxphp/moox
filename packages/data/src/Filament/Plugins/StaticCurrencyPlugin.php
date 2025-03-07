<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Data\Filament\Resources\StaticCurrencyResource;

class StaticCurrencyPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'staticcurrency';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            StaticCurrencyResource::class,
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
