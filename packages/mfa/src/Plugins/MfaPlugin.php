<?php

declare(strict_types=1);

namespace Moox\Mfa\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Mfa\Support\MfaProviders;

/**
 * Registers Filament AppAuthentication (TOTP) on allowlisted panels.
 *
 * Host apps must enable ->profile() on the panel so users can set up MFA.
 * This plugin does not call profile() to avoid conflicting with host config.
 *
 * multiFactorAuthentication() replaces providers. When also using moox/passkey,
 * register PasskeyPlugin first, then MfaPlugin — both merge providers; this
 * plugin owns isRequired from config('mfa.required'). Alternatively host-compose
 * via MfaProviders + PasskeyMfaProviders (do not also call both plugins' merges).
 */
class MfaPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'mfa';
    }

    public function register(Panel $panel): void
    {
        if (! config('mfa.enabled', false)) {
            return;
        }

        $panels = config('mfa.panels', ['admin']);

        if (! in_array($panel->getId(), $panels, true)) {
            return;
        }

        $existing = array_values($panel->getMultiFactorAuthenticationProviders());

        $panel->multiFactorAuthentication(
            array_merge($existing, MfaProviders::providers()),
            isRequired: (bool) config('mfa.required', false),
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
