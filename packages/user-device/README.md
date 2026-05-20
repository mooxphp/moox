![Moox UserDevice](https://github.com/mooxphp/moox/raw/main/art/banner/user-device.jpg)

# Moox UserDevice

Moox User Device adds device tracking and a simple “confirm device by email” flow on top of Filament logins.

## Quick Installation

These two commmands are all you need to install the package:

```bash
composer require moox/user-device
php artisan user-device:install
```

Curious what the install command does? See manual installation below.

## What it does

<!--whatdoes-->

### User flow (simple & secure)

1. **User logs in**
   - A `user_devices` record is created/updated for the user + IP + user-agent.
   - If the record is **new**, the user receives an email with a **signed “Trust this device” link**.

2. **Device is untrusted (`whitelisted = false`)**
   - The user is **hard-blocked** in Filament: any attempt to navigate away will be redirected back to the devices page with a single notification.
   - The user must click the **email trust link** to continue.

3. **Trusting a device**
   - The signed link marks the device as `whitelisted = true`.
   - After that, the user can use Filament normally.

4. **Admin actions**
   - If Filament Shield / Spatie Permission is available, **super_admin** can:
     - **Trust / Untrust** devices from the resource table
     - **Delete** a device (and sessions for that device are revoked)

### What the package ships

- **Model**
  - `Moox\UserDevice\Models\UserDevice` (stores device metadata, `whitelisted`, `ip_address`, …)

- **Tracking**
  - Listener: `Moox\UserDevice\Listeners\TrackUserDeviceOnLogin`
  - Service: `Moox\UserDevice\Services\UserDeviceTracker`

- **Enforcement**
  - Middleware: `Moox\UserDevice\Http\Middleware\EnsureTrustedDevice`
  - Panel integration: `Moox\UserDevice\UserDevicePlugin` registers the middleware as **persistent auth middleware** (important for Livewire).

- **Trust flow**
  - Route: `GET /user-device/{panel}/devices/{device}/trust` (signed)
  - Controller: `Moox\UserDevice\Http\Controllers\TrustDeviceController`

- **Email**
  - Notification: `Moox\UserDevice\Notifications\NewDeviceNotification`
  - View: `resources/views/mail/new-device.blade.php`
  - Translations: `resources/lang/{de,en}/translations.php`

- **Filament UI**
  - Resource: `Moox\UserDevice\Resources\UserDeviceResource`
  - Page: `Moox\UserDevice\Resources\UserDeviceResource\Pages\ListPage`

### Configuration (config/user-device.php)

- `enabled` (bool): master switch for login tracking, middleware enforcement, and trust routes (default: false, env: `USER_DEVICE_ENABLED`)
- `new_device_notification` (bool): send email for new device
- `trust_link_expires_minutes` (int): signed trust link expiry
- `scope_to_authenticated_user` (bool): always scope resource to the current user
- `allow_all_devices_without_shield` (bool): allow viewing all devices if Shield is not installed
- `mail_logo_url` (string): logo URL or public path for the email

### Notes

- If you want the hard-block behavior, ensure the Filament panels that should be protected load `UserDevicePlugin::make()`.

<!--/whatdoes-->

## Manual Installation

Instead of using the install-command `php artisan user-device:install` you are able to install this package manually step by step:

```bash
// Publish and run the migrations:
php artisan vendor:publish --tag="user-device-migrations"
php artisan migrate

// Publish the config file with:
php artisan vendor:publish --tag="user-device-config"
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
