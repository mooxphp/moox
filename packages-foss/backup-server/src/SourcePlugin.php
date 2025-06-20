<?php

namespace Moox\BackupServerUi;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\BackupServerUi\Resources\SourceResource;

class SourcePlugin implements Plugin
{
    public function getId(): string
    {
        return 'source';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                SourceResource::class,
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
