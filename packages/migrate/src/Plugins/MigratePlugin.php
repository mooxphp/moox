<?php

declare(strict_types=1);

namespace Moox\Migrate\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Migrate\Resources\MigrationResource;

class MigratePlugin implements Plugin
{
    public function getId(): string
    {
        return 'migrate';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            MigrationResource::class,
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
