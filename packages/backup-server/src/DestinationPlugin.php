<?php

namespace Moox\BackupServerUi;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\BackupServerUi\Resources\DestinationResource;

class DestinationPlugin implements Plugin
{
    public function getId(): string
    {
        return 'destination';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                DestinationResource::class,
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
