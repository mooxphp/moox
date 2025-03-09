<?php

namespace Moox\Item\Moox\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Item\Moox\Entities\Items\Item\ItemResource;

class ItemPlugin implements Plugin
{
    public function getId(): string
    {
        return 'item';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                ItemResource::class,
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
