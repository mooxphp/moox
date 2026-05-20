![Moox Security](https://github.com/mooxphp/moox/raw/main/art/banner/security.jpg)

# Moox Security

Password reset flows for Moox Filament panels: auth pages, queued mail notifications, admin actions on users, and a resource to manage reset tokens.

## Quick installation

```bash
composer require moox/security
php artisan security:install
```

The install command publishes config and migrations, runs migrations, and can register `ResetPasswordPlugin` in your Filament panel provider.

## Filament panel setup

Register the plugin on every panel that should support password reset (for example the admin panel):

```php
use Moox\Security\ResetPasswordPlugin;

$panel->plugins([
    // ...
    ResetPasswordPlugin::make(),
]);
```

### What `ResetPasswordPlugin` registers

When the plugin is loaded on a panel, it automatically:

1. **Password reset auth routes** — `passwordReset()` with Moox pages:
   - `Moox\Security\Services\RequestPasswordReset` (forgot password)
   - `Moox\Security\Services\ResetPassword` (set new password)

   Filament route names follow the pattern `filament.{panel}.auth.password-reset.*` (for example `filament.admin.auth.password-reset.reset`). These routes are required for reset links in emails.

2. **`ResetPasswordResource`** — list and manage rows in `password_reset_tokens` (email, `user_type`, `created_at`).

Without this plugin (or an equivalent `->passwordReset(...)` call on the panel), sending reset links from `moox/user` will fail when the notification builds the reset URL.

## Integration with `moox/user`

`moox/user` does **not** require `moox/security`. When both packages are installed:

| Feature | Where |
|--------|--------|
| Send reset link (single user, edit page) | `SendPasswordResetLinkAction` |
| Send reset links (bulk, user list) | `SendPasswordResetLinksBulkAction` |
| Change own password on edit | Built into `UserResource` (no security package needed) |

Actions are only shown when the Security action classes exist **and** `security.actions.bulkactions.sendPasswordResetLinkBulkAction` is `true` in config.

On the user edit page, the reset link action is hidden when you edit your own account (use the password section instead).

The bulk action skips the currently authenticated user (same idea as hiding the action on your own edit page). Selecting only yourself sends no mail and shows no success toast.

## Configuration

Publish config if needed:

```bash
php artisan vendor:publish --tag=security-config
```

Important keys:

| Key | Purpose |
|-----|---------|
| `resources.reset_password.single` / `.plural` | Labels for the token resource (`trans//core::security.*`) |
| `navigation_group` | Filament navigation group (default: users) |
| `auth.{guard}.*` | Column names for login / reset per guard |
| `mail_recipient_name` | User attribute used in reset emails |
| `password_reset_links.model` | User model for `users:generate-reset-links` |
| `password_reset_links.broker` | Password broker name (default: `users`) |
| `password_reset_links.panel` | Filament panel id for reset URLs in queued mails |
| `actions.bulkactions.sendPasswordResetLinkBulkAction` | Enable Filament reset actions in User / Press |

Resource labels are defined in **`packages/core/resources/lang/*/security.php`** and referenced from config with the `trans//` pattern (same as other Moox packages). Package `security::translations` is used for action labels and mail copy only.

## Password reset email (queued)

`PasswordResetNotification` implements `ShouldQueue`. It stores only the **panel id** (for example `admin`), not the Filament `Panel` instance, so the job can be serialized safely. The panel is resolved when the mail is sent.

Always create notifications with:

```php
PasswordResetNotification::forToken($token);
```

This captures the current panel id at send time. Do not store `Filament::getCurrentOrDefaultPanel()` on the notification object.

## Artisan commands

```bash
php artisan security:install          # Publish config, migrate, optional plugin registration
php artisan users:generate-reset-links # Queue reset mails for all users (see config model)
```

## Manual installation

```bash
php artisan vendor:publish --tag=security-migrations
php artisan migrate
php artisan vendor:publish --tag=security-config
```

Then add `ResetPasswordPlugin::make()` to your panel provider plugins array (see above).

## Local development (path repositories)

Path repositories install Moox packages into `vendor/` as symlinks to `packages/…`, so edits under `packages/security` are picked up without copying. If autoload or install state looks stale, refresh with:

```bash
composer reinstall moox/security
```

## Verify routes

After registering the plugin:

```bash
php artisan route:list --name=password-reset
```

You should see request and reset routes for your panel (for example `admin/password-reset/request` and `admin/password-reset/reset`).

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes.

## Security vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
