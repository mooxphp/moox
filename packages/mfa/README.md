![Moox MFA](https://github.com/mooxphp/moox/raw/main/art/banner/mfa.jpg)

# Moox MFA

Filament **App Authentication** (TOTP authenticator apps) with optional recovery codes for Moox panels.

## Concept decisions (Phase 1)

| Decision | Value |
|----------|--------|
| Scope | **Admin-only** (`App\Models\User`), panel allowlist `admin` |
| Factor | Filament `AppAuthentication` TOTP + recovery codes |
| `isRequired` | **false** by default (`MOOX_MFA_REQUIRED`); owned by this package / host |
| Device-Trust | Does **not** replace `moox/user-device` |
| Login-Link | Does **not** replace `moox/login-link` |
| Profile UI | Host must enable `->profile()` on the panel |

### Non-goals (Phase 1)

- Email MFA
- Finance / Portal auth models
- Admin force-reset of MFA secrets
- Mandatory MFA org-wide rollout

## Quick Installation

```bash
composer require moox/mfa
php artisan vendor:publish --tag=mfa-migrations
php artisan migrate
```

Or via Moox installer custom step `mfa-setup`.

### Wire-up

1. `MOOX_MFA_ENABLED=true` in `.env`
2. On `App\Models\User`:

```php
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;

class User extends Authenticatable implements HasAppAuthentication, HasAppAuthenticationRecovery
{
    use InteractsWithAppAuthentication;
    use InteractsWithAppAuthenticationRecovery;
}
```

3. Panel:

```php
use Moox\Mfa\Plugins\MfaPlugin;

$panel
    ->profile() // required for setup UI — host responsibility
    ->plugin(MfaPlugin::make());
```

### Composing with Passkeys

Filament `multiFactorAuthentication()` **replaces** providers.

**Option A — both Moox plugins** (recommended): register `PasskeyPlugin` first, then `MfaPlugin`. They merge providers; this plugin sets `isRequired` from `config('mfa.required')`.

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

**Option B — host-compose helpers** (do **not** also rely on both plugins' merges):

```php
use Moox\Mfa\Support\MfaProviders;
use Moox\Passkey\Support\PasskeyMfaProviders;

$panel->multiFactorAuthentication([
    ...MfaProviders::providers(),
    ...PasskeyMfaProviders::providers(),
], isRequired: (bool) config('mfa.required', false));
```

Pick A **or** B — do not double-call `multiFactorAuthentication` for the same providers. Do **not** also register `FilamentPasskeysPlugin` when using `PasskeyPlugin`.

## Handoff checklist (policy questions for the host)

- [ ] **Self-Service** — may users enable/disable MFA (TOTP + recovery codes) themselves on profile?
- [ ] **Admin reset** — may ops reset another user's factors? Which permission? (out of Phase 1)
- [ ] **Pflicht** — optional vs required (`MOOX_MFA_REQUIRED`), per panel / role?
- [ ] **Finance / Portal follow-up** — Phase 1 is Admin-only; when do other auth models get MFA?
- [ ] **Challenge order** — Login-Link vs Device-Trust vs MFA/Passkey sequencing (document per host)
- [ ] **Passkeys** — passwordless / managementOnly via `moox/passkey`; not a substitute for TOTP in Phase 1

## Config

See `config/mfa.php`: `enabled`, `required`, `recoverable`, `panels`.

## Security

Please review [SECURITY.md](SECURITY.md).

## License

MIT — see [LICENSE.md](LICENSE.md).
