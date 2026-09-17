<?php

declare(strict_types=1);

namespace Moox\VeraPdf\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\VeraPdf\Resources\VeraPdfValidationResource;

final class VeraPdfPlugin implements Plugin
{
    public function getId(): string
    {
        return 'verapdf';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            VeraPdfValidationResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
    }

    public static function make(): static
    {
        return app(self::class);
    }
}
