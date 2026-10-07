<?php

declare(strict_types=1);

namespace Moox\Contact\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Contact\Resources\ContactResource;

class ContactPlugin implements Plugin
{
    public function getId(): string
    {
        return 'contact';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            ContactResource::class,
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
