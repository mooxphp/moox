![Moox LoginLink](https://github.com/mooxphp/moox/raw/main/art/banner/login-link.jpg)

# Moox LoginLink

Passwordless login links for Filament panels, integrated into the native Filament login page.

## What it does

- Generates **temporary signed** login links (expires + signature).
- Enforces **single-use** (marks links as used, and invalidates previous valid links for the same user + panel).
- **Panel-aware**: login links are only valid for the panel they were created for.
- Sends email via the queue (uses `Mail::queue()`).
- Adds a “Send login link” action directly on Filament’s login form (no extra public routes required).

## How it works (high level)

- The Filament login page adds a **“Send login link”** action on the email/login field.
- When requested, it creates a `login_links` record and queues a `LoginLinkEmail`.
- The email contains a **temporary signed URL** to `{panel}/login-link/{id}`.
- When the link is opened:
  - Laravel validates the URL signature and expiry
  - the `login_links` record is locked and marked as used (single-use)
  - the user is logged in and redirected into the panel
- Invalid or expired links redirect to the login page with a danger notification.

## Installation

```bash
composer require moox/login-link
php artisan login-link:install
```

Ensure a **queue worker** is running (`php artisan queue:work`) so login-link emails are sent.

## Filament panel setup

Register the plugin on every panel that should support passwordless login:

```php
use Moox\LoginLink\Plugins\LoginLinkPlugin;

$panel->plugins([
    // ...
    LoginLinkPlugin::make(),
]);
```

No other login-link wiring is required in `AdminPanelProvider` (no custom `->login()` class needed).

The plugin automatically extends **your panel's configured login class** (Filament default, `Moox\User\Services\Login`, or a custom `->login(YourLogin::class)`) with the login-link hint on the email/login field.

If your login page does not use `getLoginFormComponent()` or `getEmailFormComponent()`, add `Moox\LoginLink\Concerns\InteractsWithLoginLinks` yourself and call `configureLoginFormWithMagicLink()` on the identifier field.

## Why we use a trait (and a note about PHPStan)

The passwordless UI/behavior is implemented as a trait (`Moox\LoginLink\Concerns\InteractsWithLoginLinks`) so it can be **mixed into any panel login page** (Filament default or a custom `->login(...)`) without forcing a fixed replacement class.

The plugin applies the trait dynamically via `PanelLoginEnhancer` (it generates an enhanced login class at runtime). Because of that, PHPStan may report `trait.unused` even though the trait **is used at runtime** (PHPStan cannot see the `use ...` inside the runtime-generated class).

## Key configuration knobs

- `login-link.passwordless.enabled`: enable/disable the passwordless integration.
- `login-link.rate_limit.send`: limits for unauthenticated magic-link requests (per IP + per IP/email).
- `login-link.expiration_minutes`: link validity window.
- `login-link.user_models`: allowed user models (must include the model used by your panel auth guard provider).
- `login-link.mail_logo_url`: optional logo shown in the email template.

## Security notes

- The email flow is **non-enumerating** from the UI perspective (same success message when the address is unknown).
- Links are **signed + expiring** and **single-use** (server-enforced).
- Panel access is enforced via `FilamentUser::canAccessPanel()`.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
