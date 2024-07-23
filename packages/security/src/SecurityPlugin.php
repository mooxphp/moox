<?php

namespace Moox\Security;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Security\Resources\SecurityResource;

class SecurityPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'securities';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            SecurityResource::class,
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
