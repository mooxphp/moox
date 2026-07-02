# Moox Frontend Auth

`moox/frontend-auth` protects your frontend (public web) routes by requiring an authenticated user. It uses Filament's auth checks so access decisions stay consistent with Filament.

If a guest hits a protected route, they are redirected to the Filament login page.

This package is intended to work with your existing `web` routing stack and Filament panels.

## Features

- Protects routes with `Filament::auth()->check()`
- Automatically allows Filament login requests through (so users can sign in)
- Redirects unauthenticated users to the Filament login URL
- Optional ability to configure the Laravel guard/provider based on package config

## How it works

When enabled, the package:

1. If enabled, configures the Laravel auth guard/provider (guard + user model) so the configured user model can be used for authentication.
2. If the request targets a Filament login route, it is allowed through.
3. For all other requests: checks `Filament::auth()->check()`.
4. If the user is not authenticated: redirects to the configured login URL.
5. Stores the desired post-login target in `session('url.intended')` (using `redirect_after_login`).

## Requirements

- Laravel 12
- Filament v5
- A Filament panel with a working login route (`Filament::getLoginUrl()` is used for redirects)

## Installation

```bash
composer require moox/frontend-auth
```

### Publish configuration

```bash
php artisan vendor:publish --tag=moox-frontend-auth-config
```

Then configure `config/moox-frontend-auth.php`.

If your setup uses provider-based publishing instead of tags, you can also try:

```bash
php artisan vendor:publish --provider="Moox\\FrontendAuth\\FrontendAuthServiceProvider"
```

## Usage

Apply the middleware to the routes you want to protect.

### Option A: Use the provided helper (recommended)

```php
use Illuminate\Support\Facades\Route;

Route::middleware(moox_frontend_auth_middleware())->group(function () {
    Route::get('/', function () {
        return view('page.home');
    });
});
```

Note: `moox_frontend_auth_middleware()` reads `config/moox-frontend-auth.php`. By default it returns only `['moox.frontend-auth']` to avoid running the `web` middleware group twice.

### Option B: Use the middleware alias directly

```php
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'moox.frontend-auth'])->group(function () {
    Route::get('/', fn () => view('page.home'));
});
```

## Configuration

Default config (`config/moox-frontend-auth.php`) includes:

```php
return [
    'enabled' => true,
    'guard' => 'web',
    'user_model' => \App\Models\User::class,
    // default is intentionally minimal to avoid running the `web` middleware group twice
    'middleware' => ['moox.frontend-auth'],
    'redirect_after_login' => '/',
    // if left as '/login', the middleware will automatically redirect to Filament's login URL
    'redirect_if_guest' => '/login',
];
```

### Settings

`enabled`
: Turn the middleware on/off.

`guard`
: Laravel auth guard used for authentication (and applied to Filament's panel auth guard where possible).

`user_model`
: The Eloquent user model used by the auth provider for the configured guard.

`middleware`
: The middleware array returned by `moox_frontend_auth_middleware()`.

`redirect_after_login`
: Stored in `session('url.intended')` and used after a successful login.

`redirect_if_guest`
: Where guests are redirected when unauthenticated.

If this is left as `/login` (or blank), the middleware redirects to `Filament::getLoginUrl()` automatically.

## Notes on the Filament login exclusion

The middleware skips protection for login requests by checking:

- The route name contains `.auth.login` (when available), OR
- The request path matches the path part of `Filament::getLoginUrl()`

## Helper functions

The package ships a few helper functions (available after Composer autoload):

- `moox_frontend_auth_enabled(): bool`
- `moox_frontend_auth_middleware(): array<string>`
- `moox_frontend_auth_user_model(): string`

## Redirect behavior (important)

- Filament login requests are excluded from protection (so users can log in).
- For non-authenticated users, redirects align with Filament by using `Filament::auth()->check()` and `Filament::getLoginUrl()` (depending on `redirect_if_guest`).

## License

MIT

