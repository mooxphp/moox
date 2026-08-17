![Moox LoginLink](https://github.com/mooxphp/moox/raw/main/art/banner/login-link.jpg)

# Moox LoginLink

Signed-link **process engine** for Laravel/Filament. Login (magic link) is the first process; public verify/ack-style flows use the same lifecycle without auth.

> Package rename is deferred until the engine boundaries are stable.

## Architecture

| Layer | Owns |
|---|---|
| **Core** | Signed URL, expiry, single-use, issue/resend, subject, process, payload, template key → config view |
| **Process-specific** | Auth/panel (login only); domain handlers in consumer packages |

- Process `context`: `auth` (panel) or `public` (no auth)
- Process `invalidate_prior`: whether a new issue marks prior valid links used (default `true`)
- Link `payload`: optional JSON call context (campaign ids, etc.) — subject stays the identity
- Templates: process stores `template_key` only; views are mapped in `login-link.templates` config (no domain knowledge in the engine)
- Bulk: core issues **one** link; callers loop/queue for mass send

## What it does

- Generates **temporary signed** links (expires + signature).
- Enforces **single-use** (and optional invalidate-prior per process).
- **Auth context**: panel-bound login consume route + guard authentication.
- **Public context**: panel-free consume route (`signed-link/{loginLink}` by default).
- Sends email via the queue (`Mail::queue()`).
- Adds a “Send login link” action on Filament’s login form (auth process).

## How it works (high level)

- Auth login: Filament login → issue `login` process → panel consume URL → `login` handler authenticates.
- Public process: `LoginLinkService::issue(...)` with `panelId: null` → public consume URL → handler (e.g. `ack`) runs **without** Auth.
- Redemption looks up the process definition and dispatches to its **handler_key**.
- Other packages register handlers under `{package}.login-link.handlers`.

### Public / non-login example

```php
app(\Moox\LoginLink\Services\LoginLinkService::class)->issue(
    processSlug: 'ack',
    subject: $address,
    email: 'ap@example.com',
    panelId: null, // required null for public-context processes
    request: request(),
    payload: ['source' => 'portal'],
);
```

Register real verification handlers in consumer packages; `ack` is the built-in proof handler.

### Demo with dump views

```bash
php artisan db:seed --class="Moox\LoginLink\Database\Seeders\LoginLinkProcessSeeder"
php artisan login-link:demo
# or campaign-style (invalidate_prior off):
php artisan login-link:demo demo-campaign --payload='{"campaign":"spring","variant":"A"}'
```

1. Open the printed signed URL (or the queued dump mail).
2. Land on `/login-link/demo/dump` — JSON dump of process, subject, payload, `auth.check=false`.

## Installation

```bash
composer require moox/login-link
php artisan login-link:install
```

Ensure a **queue worker** is running (`php artisan queue:work`) so emails are sent.

## Filament panel setup

```php
use Moox\LoginLink\Plugins\LoginLinkPlugin;

$panel->plugins([
    LoginLinkPlugin::make(),
]);
```

## Process definitions

Admins manage processes under **Link processes**:

- `title`, `slug`, `context` (`auth` \| `public`)
- `template_key` (from `login-link.templates`)
- `handler_key` (registered handler)
- `mail_from`, optional `content` (passed into the view, not the template selector)
- `expiry_minutes`, `invalidate_prior`

Seeded on install:

- `login` — auth, template `login`, invalidate prior on
- `ack` — public, template `ack`, invalidate prior on

## Key configuration knobs

- `login-link.handlers` — handler key → class
- `login-link.templates` — template key → Blade view
- `login-link.public_consume_path` — public signed route path
- `login-link.public_invalid_redirect` — redirect when public redeem fails
- `login-link.ack.redirect_url` — ack handler success redirect
- `login-link.passwordless.enabled`, `rate_limit.send`, `expiration_minutes`, `user_models`, `mail_logo_url`

## Security notes

- Auth links are panel-scoped; public links redeem only on the public route.
- Links are **signed + expiring** and **single-use** (server-enforced).
- Panel access for login uses `FilamentUser::canAccessPanel()`.

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
