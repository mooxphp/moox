<?php

use App\Models\User;

return [
    'enabled' => true,
    'guard' => 'web',
    'user_model' => User::class,
    // This package is intended to be used together with the existing `web` routing stack.
    // Keep the default minimal to avoid running the `web` middleware group twice.
    'middleware' => ['moox.frontend-auth'],
    'redirect_after_login' => '/',
    // If you keep the default '/login', the middleware will automatically redirect to Filament's login URL.
    'redirect_if_guest' => '/login',
];
