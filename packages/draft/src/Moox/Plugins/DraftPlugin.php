<?php

namespace Moox\Draft\Moox\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Draft\Moox\Entities\Drafts\Draft\DraftResource;

class DraftPlugin implements Plugin
{
    public function getId(): string
    {
        return 'draft';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                DraftResource::class,
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
