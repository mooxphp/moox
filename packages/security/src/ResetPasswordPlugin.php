<?php

namespace Moox\Security;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Security\Resources\ResetPasswordResource;

class ResetPasswordPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'reset-password';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            ResetPasswordResource::class,
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
