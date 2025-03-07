<?php

declare(strict_types=1);

namespace Moox\Connect\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Connect\Resources\ApiEndpointResource;

final class ApiEndpointPlugin implements Plugin
{
    public function getId(): string
    {
        return 'connect-api-endpoint';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            ApiEndpointResource::class,
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
