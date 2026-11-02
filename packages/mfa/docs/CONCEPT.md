# MFA & Passkeys — Phase 1 defaults

Short concept summary for `moox/mfa` and interplay with `moox/passkey`.

## Defaults table

| Concern | Default | Notes |
|---------|---------|--------|
| MFA master switch | `MOOX_MFA_ENABLED=false` | Plugin no-op until enabled |
| MFA required | `MOOX_MFA_REQUIRED=false` | Filament `isRequired` |
| Recoverable | `true` | Recovery codes column + AppAuthentication::recoverable() |
| MFA panels | `['admin']` | Allowlist |
| User model | `App\Models\User` | Admin Phase 1 only |
| Passkey master switch | `MOOX_PASSKEY_ENABLED=false` | |
| Passwordless login | `MOOX_PASSKEY_PASSWORDLESS=true` | Via FilamentPasskeysPlugin |
| Passkey panels | `['admin']` | Allowlist |
| Passkey as 2FA | Phase 2 | Phase 1 uses `managementOnly()` |
| Device-Trust | Separate | `moox/user-device` unchanged |
| Login-Link | Separate | unchanged |

## Stack

- **TOTP:** Filament `Filament\Auth\MultiFactor\App\AppAuthentication`
- **Passkeys:** `laravel/passkeys` + `adriaanzon/filament-passkeys` (no custom WebAuthn)

## Composition

`Panel::multiFactorAuthentication()` replaces providers. Host (or plugin merge) must combine `MfaProviders` + `PasskeyMfaProviders`. Profile page required for setup UIs.

## Non-goals

Email MFA, Portal/Finance models, admin force-reset, custom WebAuthn, Cloud IdP, mandatory rollout automation.
