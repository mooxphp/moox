<?php

declare(strict_types=1);

namespace Moox\Passkey\Support;

use AdriaanZon\FilamentPasskeys\PasskeyAuthentication;
use Filament\Auth\MultiFactor\Contracts\MultiFactorAuthenticationProvider;

/**
 * Passkey MFA provider factories for host panel composition.
 *
 * Phase 1 returns managementOnly() so passkeys appear on the profile page
 * but are excluded from the MFA challenge (passwordless login covers sign-in).
 * Passkey-as-2FA is Phase 2.
 */
class PasskeyMfaProviders
{
    /**
     * @return array<int, MultiFactorAuthenticationProvider>
     */
    public static function providers(): array
    {
        return [
            PasskeyAuthentication::make()->managementOnly(),
        ];
    }
}
