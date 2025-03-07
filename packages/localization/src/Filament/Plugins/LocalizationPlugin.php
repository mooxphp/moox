<?php

declare(strict_types=1);

namespace Moox\Localization\Filament\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Localization\Filament\Resources\LocalizationResource;

class LocalizationPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'moox-localization';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            LocalizationResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}

    public static function make(): static
    {
        return app(static::class);
    }
}
