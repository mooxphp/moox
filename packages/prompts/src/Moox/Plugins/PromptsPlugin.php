<?php

namespace Moox\Prompts\Moox\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Prompts\Filament\Pages\RunCommandPage;
use Moox\Prompts\Filament\Resources\CommandExecutionResource;

class PromptsPlugin implements Plugin
{
    public function getId(): string
    {
        return 'moox-prompts';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->pages([
                RunCommandPage::class,
            ])
            ->resources([
                CommandExecutionResource::class,
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
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
