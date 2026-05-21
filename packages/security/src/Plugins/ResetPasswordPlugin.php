<?php

namespace Moox\Security\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Security\Resources\ResetPasswordResource;
use Moox\Security\Services\RequestPasswordReset;
use Moox\Security\Services\ResetPassword;

/**
 * Filament plugin for {@see ResetPasswordResource} (password reset tokens).
 *
 * Part of the moox/security package — register alongside other panel plugins.
 */
class ResetPasswordPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'reset-password';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->passwordReset(RequestPasswordReset::class, ResetPassword::class)
            ->resources([
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
