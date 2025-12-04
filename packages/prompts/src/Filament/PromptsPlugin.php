<?php

namespace Moox\Prompts\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Prompts\Filament\Pages\RunCommandPage;

class PromptsPlugin implements Plugin
{
    public function getId(): string
    {
        return 'moox-prompts';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            RunCommandPage::class,
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

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }
}

