<?php

declare(strict_types=1);

namespace Moox\DataLanguages\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\DataLanguages\Resources\StaticCountriesStaticCurrenciesResource;

class StaticCountriesStaticCurrenciesPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'staticcountriesstaticcurrencies';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            StaticCountriesStaticCurrenciesResource::class,
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
