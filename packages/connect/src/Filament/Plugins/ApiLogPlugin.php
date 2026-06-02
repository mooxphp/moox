<?php

declare(strict_types=1);

namespace Moox\Connect\Filament\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Connect\Filament\Resources\ApiLogResource;

class ApiLogPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'apilog';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            ApiLogResource::class,
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
