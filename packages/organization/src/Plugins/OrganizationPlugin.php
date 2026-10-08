<?php

declare(strict_types=1);

namespace Moox\Organization\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Organization\Resources\OrganizationTypeResource;

class OrganizationPlugin implements Plugin
{
    public function getId(): string
    {
        return 'organization';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            OrganizationTypeResource::class,
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
