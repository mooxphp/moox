<?php

declare(strict_types=1);

namespace Moox\Connect\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Connect\Resources\ApiLogResource;

final class ApiLogPlugin implements Plugin
{
    public function getId(): string
    {
        return 'connect-api-log';
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
        return app(self::class);
    }
}
