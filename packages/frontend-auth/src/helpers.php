<?php

declare(strict_types=1);
use App\Models\User;

if (! function_exists('moox_frontend_auth_enabled')) {
    function moox_frontend_auth_enabled(): bool
    {
        return config('moox-frontend-auth.enabled', true);
    }
}

if (! function_exists('moox_frontend_auth_middleware')) {
    /**
     * @return array<int, string>
     */
    function moox_frontend_auth_middleware(): array
    {
        return config('moox-frontend-auth.middleware', ['web', 'auth']);
    }
}

if (! function_exists('moox_frontend_auth_user_model')) {
    function moox_frontend_auth_user_model(): string
    {
        return config('moox-frontend-auth.user_model', User::class);
    }
}
