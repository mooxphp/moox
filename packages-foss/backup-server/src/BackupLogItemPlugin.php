<?php

namespace Moox\BackupServerUi;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\BackupServerUi\Resources\BackupLogItemResource;

class BackupLogItemPlugin implements Plugin
{
    public function getId(): string
    {
        return 'backup-log';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                BackupLogItemResource::class,
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
