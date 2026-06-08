<?php

declare(strict_types=1);

namespace Moox\KositValidator\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\KositValidator\Resources\KositValidationResource;

final class KositValidatorPlugin implements Plugin
{
    public function getId(): string
    {
        return 'kosit-validator';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            KositValidationResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}

    public static function make(): static
    {
        return app(self::class);
    }
}
