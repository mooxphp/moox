<?php

declare(strict_types=1);

namespace Moox\Contact\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Contact\Resources\ContactResource;
use Moox\Core\Support\Resources\ChildResourceRegistrar;

class ContactPlugin implements Plugin
{
    public function getId(): string
    {
        return 'contact';
    }

    public function register(Panel $panel): void
    {
        ChildResourceRegistrar::registerFromParentDefinition(
            $panel,
            ContactResource::class,
            'contact',
            config('contact.resources.contact', []),
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
