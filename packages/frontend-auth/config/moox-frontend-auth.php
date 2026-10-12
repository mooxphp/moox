<?php

use App\Models\User;

return [
    /*
    |--------------------------------------------------------------------------
    | Enable frontend auth gate
    |--------------------------------------------------------------------------
    |
    | When true, FrontendAuthMiddleware is active on the `web` stack (including
    | Livewire upload/update). Finance/portal-only sessions can be blocked by
    | the default admin guard — set MOOX_FRONTEND_AUTH_ENABLED=false to turn
    | the gate off until panel-aware Livewire auth is sorted.
    |
    */
    'enabled' => filter_var(env('MOOX_FRONTEND_AUTH_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'guard' => 'web',
    'user_model' => User::class,
    // This package is intended to be used together with the existing `web` routing stack.
    // Keep the default minimal to avoid running the `web` middleware group twice.
    'middleware' => ['moox.frontend-auth'],
    'redirect_after_login' => '/',
    // If you keep the default '/login', the middleware will automatically redirect to Filament's login URL.
    'redirect_if_guest' => '/login',
];
