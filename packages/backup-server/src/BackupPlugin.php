<?php

namespace Moox\BackupServerUi;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\BackupServerUi\Resources\BackupResource;

class BackupPlugin implements Plugin
{
    public function getId(): string
    {
        return 'backup';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                BackupResource::class,
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
