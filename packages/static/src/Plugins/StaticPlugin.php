<?php

declare(strict_types=1);

namespace Moox\Static\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Core\Support\Resources\ChildResourceRegistrar;
use Moox\Static\Resources\StaticEntryResource;

class StaticPlugin implements Plugin
{
    public function getId(): string
    {
        return 'static';
    }

    public function register(Panel $panel): void
    {
        ChildResourceRegistrar::registerFromParentDefinition(
            $panel,
            StaticEntryResource::class,
            'static_entry',
            config('static.resources.static_entry', []),
        );
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
