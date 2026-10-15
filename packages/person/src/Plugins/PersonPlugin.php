<?php

declare(strict_types=1);

namespace Moox\Person\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Person\Resources\PersonResource;

class PersonPlugin implements Plugin
{
    public function getId(): string
    {
        return 'person';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            PersonResource::class,
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
