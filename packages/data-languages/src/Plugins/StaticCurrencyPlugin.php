<?php

declare(strict_types=1);

namespace Moox\DataLanguages\Plugins;

use Moox\DataLanguages\Resources\StaticCurrencyResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;

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
