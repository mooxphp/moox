<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Master switch for TOTP MFA. When false, MfaPlugin is a no-op even if
    | listed on a panel.
    |
    */
    'enabled' => env('MOOX_MFA_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Required
    |--------------------------------------------------------------------------
    |
    | When true, Filament forces users to set up MFA before using the panel
    | (isRequired on multiFactorAuthentication). Default off for Phase 1.
    |
    */
    'required' => env('MOOX_MFA_REQUIRED', false),

    /*
    |--------------------------------------------------------------------------
    | Recoverable
    |--------------------------------------------------------------------------
    |
    | Enable Filament AppAuthentication recovery codes.
    |
    */
    'recoverable' => true,

    /*
    |--------------------------------------------------------------------------
    | Panels
    |--------------------------------------------------------------------------
    |
    | Allowlist of Filament panel IDs that may register MFA.
    | Phase 1: admin only.
    |
    */
    'panels' => ['admin'],

    /*
    |--------------------------------------------------------------------------
    | Installer / next steps (documentation for operators)
    |--------------------------------------------------------------------------
    |
    | After migrations: set MOOX_MFA_ENABLED=true, implement
    | HasAppAuthentication + HasAppAuthenticationRecovery on App\Models\User,
    | register MfaPlugin on the panel, and ensure ->profile() is enabled so
    | users can set up TOTP from their profile.
    |
    */
];
