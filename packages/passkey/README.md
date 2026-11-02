![Moox Passkey](https://github.com/mooxphp/moox/raw/main/art/banner/passkey.jpg)

# Moox Passkey

Thin Moox wrapper around **`laravel/passkeys`** + **`adriaanzon/filament-passkeys`**.

No custom WebAuthn stack — Phase 1 focuses on passwordless sign-in and profile passkey management.

## Concept decisions (Phase 1)

| Decision | Value |
|----------|--------|
| Scope | Admin panel allowlist |
| Passwordless | On by default (`MOOX_PASSKEY_PASSWORDLESS`) |
| Passkey as 2FA | **Phase 2** — Phase 1 uses `PasskeyAuthentication::managementOnly()` |
| Legacy admin Resource | **Removed** — laravel/passkeys only |
| Device-Trust | Orthogonal to `moox/user-device` |
| `isRequired` | Owned by **moox/mfa** / host only — PasskeyPlugin never sets it from MFA config |

### Non-goals

- Custom WebAuthn / `web-auth/webauthn-lib` integration
- Cloud IdP / social passkey brokers
- Passkey-as-MFA challenge (Phase 2)
- Replacing Device-Trust or Login-Link

## Quick Installation

```bash
composer require moox/passkey
php artisan vendor:publish --tag=passkeys-migrations
php artisan vendor:publish --tag=passkeys-config
php artisan migrate
```

Or Moox installer step `passkey-setup`.

### Wire-up

1. `MOOX_PASSKEY_ENABLED=true`
2. Configure RP ID / HTTPS / `APP_URL` for WebAuthn
3. User model:

```php
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;

class User extends Authenticatable implements PasskeyUser
{
    use PasskeyAuthenticatable;
}
```

4. Panel:

```php
use Moox\Passkey\Plugins\PasskeyPlugin;

$panel
    ->profile()
    ->plugin(PasskeyPlugin::make());
```

Do **not** also register `FilamentPasskeysPlugin` on the panel — `PasskeyPlugin` owns that registration (including passwordless when configured).

When `MOOX_PASSKEY_ENABLED=false`, `PasskeyServiceProvider` calls `Passkeys::ignoreRoutes()` so laravel/passkeys routes are not registered.

### Composing with TOTP MFA (`moox/mfa`)

**Option A — both Moox plugins** (recommended): register `PasskeyPlugin` first, then `MfaPlugin`. They merge providers; MFA owns `isRequired` from `config('mfa.required')`.

```php
use Moox\Mfa\Plugins\MfaPlugin;
use Moox\Passkey\Plugins\PasskeyPlugin;

$panel
    ->profile()
    ->plugins([
        PasskeyPlugin::make(),
        MfaPlugin::make(),
    ]);
```

**Option B — host-compose helpers** (do **not** also rely on both plugins' `multiFactorAuthentication` merges):

```php
use Moox\Mfa\Support\MfaProviders;
use Moox\Passkey\Support\PasskeyMfaProviders;

$panel->multiFactorAuthentication([
    ...MfaProviders::providers(),
    ...PasskeyMfaProviders::providers(),
], isRequired: (bool) config('mfa.required', false));
```

Pick A **or** B — do not double-call `multiFactorAuthentication` for the same providers.

## Interplay with Device-Trust

- **Passkeys** prove possession of a credential at login / management time.
- **Device-Trust** (`moox/user-device`) tracks and optionally gates browser devices after login.
- They stack: passwordless or password+TOTP login can still hit device trust middleware.

## Handoff checklist (policy questions for the host)

- [ ] **Self-Service** — may users register/remove passkeys themselves on profile?
- [ ] **Admin reset** — may ops revoke another user's passkeys? Which permission?
- [ ] **Pflicht** — optional vs required (panel/role)? Phase 1 is management-only, not 2FA challenge; required MFA is owned by `moox/mfa`
- [ ] **Finance / Portal follow-up** — Phase 1 is Admin-only; when do other auth models get passkeys?
- [ ] **Challenge order** — Login-Link vs Device-Trust vs MFA/Passkey sequencing (document per host)
- [ ] RP ID + HTTPS verified in staging/production
- [ ] User implements `PasskeyUser` + `PasskeyAuthenticatable`; profile enabled for registration UI
- [ ] Compose with `moox/mfa` via plugins (Passkey then Mfa) or helpers — not both
- [ ] Phase 2: drop `managementOnly()` if passkeys should challenge as 2FA

## Config

See `config/passkey.php`: `enabled`, `passwordless_login`, `panels`.

## Security

Please review [SECURITY.md](SECURITY.md).

## License

MIT — see [LICENSE.md](LICENSE.md).
