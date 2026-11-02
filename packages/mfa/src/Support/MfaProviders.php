<?php

declare(strict_types=1);

namespace Moox\Mfa\Support;

use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Contracts\MultiFactorAuthenticationProvider;

/**
 * MFA provider factories for host panel composition.
 *
 * Filament's multiFactorAuthentication() replaces the provider list. When
 * combining moox/mfa with moox/passkey, merge both helpers in AdminPanelProvider
 * (or let each plugin merge onto existing providers).
 */
class MfaProviders
{
    /**
     * @return array<int, MultiFactorAuthenticationProvider>
     */
    public static function providers(): array
    {
        $app = AppAuthentication::make();

        if (config('mfa.recoverable', true)) {
            $app->recoverable();
        }

        return [$app];
    }
}
