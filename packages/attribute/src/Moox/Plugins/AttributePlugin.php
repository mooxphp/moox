<?php

namespace Moox\Attribute\Moox\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Attribute\Moox\Entities\Attribute\AttributeResource;

class AttributePlugin implements Plugin
{
    public function getId(): string
    {
        return 'attribute';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                AttributeResource::class,
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
