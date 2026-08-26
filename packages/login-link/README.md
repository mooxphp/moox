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
- Public process: `LoginLinkService::issue(...)` with `panelId: null` → public consume URL → handler (e.g. `verify-email`) runs **without** Auth.
- Redemption looks up the process definition and dispatches to its **handler_key**.
- Other packages register handlers under `{package}.login-link.handlers`.

### Packaged examples

Seeded processes, each with its own English mail template:

| Process | Context | Template | Handler | Invalidate prior |
|---|---|---|---|---|
| `login` | auth | `login-link::mail.login-link` | signs the user into the panel | yes |
| `verify-email` | public | `login-link::mail.verify-email` | confirms mailbox ownership, no login | yes |
| `mass-mail` | public | `login-link::mail.mass-mail` | confirms a campaign recipient, no login | **no** |

```php
app(\Moox\LoginLink\Services\LoginLinkService::class)->issue(
    processSlug: 'verify-email',
    subject: $user,
    email: 'owner@example.com',
    panelId: null,
    request: request(),
);

app(\Moox\LoginLink\Services\LoginLinkService::class)->issue(
    processSlug: 'mass-mail',
    subject: $contact,
    email: 'reader@example.com',
    panelId: null,
    request: request(),
    payload: ['campaign' => 'Spring newsletter', 'mailing_id' => '2026-03-01'],
);
```

```bash
php artisan db:seed --class="Moox\LoginLink\Database\Seeders\LoginLinkProcessSeeder"
php artisan login-link:example
php artisan login-link:example mass-mail --payload='{"campaign":"Spring newsletter","mailing_id":"demo-001"}'
```

1. Open the printed signed URL (or the queued example mail).
2. Land on `/login-link/examples/email-verified` or `/login-link/examples/mailing-confirmed`.
3. Preview the three English mail templates at `/login-link/examples` (no queue needed).
4. Passwordless login is issued from the Filament login form, not this command.

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

- `login` — auth, template `login`, signs the user in, invalidate prior on
- `verify-email` — public, template `verify-email`, mailbox confirmation, invalidate prior on
- `mass-mail` — public, template `mass-mail`, campaign confirmation, invalidate prior **off**

## Key configuration knobs

- `login-link.passwordless.enabled`: enable/disable the passwordless integration.
- `login-link.rate_limit.send`: limits for unauthenticated magic-link requests (per IP + per IP/email).
- `login-link.expiration_minutes`: link validity window.
- `login-link.user_models`: allowed user models (must include the model used by your panel auth guard provider).
- `login-link.mail_logo_url`: optional logo shown when no MailTemplate row is used.
- `login-link.mail_template_key`: optional MailTemplate key when `moox/mail-outbox` is installed in the host (default `login-link`).

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
