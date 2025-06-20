<?php

namespace Moox\LoginLink;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\LoginLink\Resources\LoginLinkResource;

class LoginLinkPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'login-link';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            LoginLinkResource::class,
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
