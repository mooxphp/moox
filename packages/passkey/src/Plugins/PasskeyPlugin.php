<?php

declare(strict_types=1);

namespace Moox\Passkey\Plugins;

use AdriaanZon\FilamentPasskeys\FilamentPasskeysPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Passkey\Support\PasskeyMfaProviders;

/**
 * Thin wrapper around adriaanzon/filament-passkeys.
 *
 * Phase 1: passwordless login + profile management via PasskeyAuthentication
 * managementOnly(). Do not also register FilamentPasskeysPlugin on the panel —
 * this plugin owns that registration.
 *
 * Host must enable ->profile() for passkey management UI.
 * multiFactorAuthentication() replaces providers — this plugin merges
 * PasskeyMfaProviders onto any existing providers (e.g. from moox/mfa).
 *
 * isRequired is never read from config('mfa.required'); Passkey always passes
 * false so moox/mfa (or the host) owns the required flag. Register MfaPlugin
 * after PasskeyPlugin when both are used.
 */
class PasskeyPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'passkey';
    }

    public function register(Panel $panel): void
    {
        if (! config('passkey.enabled', false)) {
            return;
        }

        $panels = config('passkey.panels', ['admin']);

        if (! in_array($panel->getId(), $panels, true)) {
            return;
        }

        $filamentPasskeys = FilamentPasskeysPlugin::make();

        if (config('passkey.passwordless_login', true)) {
            $filamentPasskeys->passwordlessLogin();
        }

        $panel->plugin($filamentPasskeys);

        // managementOnly: profile UI without passkey MFA challenge (Phase 1).
        // Never touch MFA required — host / MfaPlugin owns that flag.
        $existing = array_values($panel->getMultiFactorAuthenticationProviders());

        $panel->multiFactorAuthentication(
            array_merge($existing, PasskeyMfaProviders::providers()),
            isRequired: false,
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
