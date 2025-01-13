<?php

declare(strict_types=1);

namespace App\Locale\Plugins;

use App\Locale\Resources\StaticCountriesStaticTimezonesResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;

class StaticCountriesStaticTimezonesPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'staticcountriesstatictimezones';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            StaticCountriesStaticTimezonesResource::class,
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
