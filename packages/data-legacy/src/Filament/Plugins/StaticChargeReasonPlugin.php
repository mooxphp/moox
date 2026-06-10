<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Filament\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\DataLegacy\Filament\Resources\StaticChargeReasonResource;

class StaticChargeReasonPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'staticchargereason';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            StaticChargeReasonResource::class,
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
