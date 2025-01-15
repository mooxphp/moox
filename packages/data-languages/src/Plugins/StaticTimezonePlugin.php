<?php

declare(strict_types=1);

namespace Moox\DataLanguages\Plugins;

use Moox\DataLanguages\Resources\StaticTimezoneResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;

class StaticTimezonePlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'statictimezone';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            StaticTimezoneResource::class,
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
