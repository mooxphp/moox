<?php

namespace Moox\UserSession;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\UserSession\Resources\UserSessionResource;

class UserSessionPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'user-session';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            UserSessionResource::class,
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
