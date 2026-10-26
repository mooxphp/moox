<?php

declare(strict_types=1);

namespace Moox\Salutation\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Salutation\Resources\HonorificTitleResource;
use Moox\Salutation\Resources\SalutationResource;

class SalutationPlugin implements Plugin
{
    public function getId(): string
    {
        return 'salutation';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            // SalutationResource::class,
            // HonorificTitleResource::class,
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
