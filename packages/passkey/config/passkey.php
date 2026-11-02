<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Master switch for passkey integration. When false, PasskeyPlugin is a
    | no-op even if listed on a panel.
    |
    */
    'enabled' => env('MOOX_PASSKEY_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Passwordless login
    |--------------------------------------------------------------------------
    |
    | When true, registers FilamentPasskeysPlugin with passwordlessLogin().
    | Phase 1 default: passwordless sign-in; passkeys as MFA challenge is Phase 2.
    |
    */
    'passwordless_login' => env('MOOX_PASSKEY_PASSWORDLESS', true),

    /*
    |--------------------------------------------------------------------------
    | Panels
    |--------------------------------------------------------------------------
    |
    | Allowlist of Filament panel IDs that may register passkeys.
    | Phase 1: admin only.
    |
    */
    'panels' => ['admin'],
];
