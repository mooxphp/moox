<?php

/*
|--------------------------------------------------------------------------
| Moox Configuration
|--------------------------------------------------------------------------
|
| This configuration file uses translatable strings. If you want to
| translate the strings, you can do so in the language files
| published from moox_core. Example:
|
| 'trans//core::core.all',
| loads from common.php
| outputs 'All'
|
*/

return [

    // Enable firewall?
    'enabled' => env('MOOX_FIREWALL_ENABLED', false),

    // Whitelist IP addresses
    'whitelist' => array_filter(explode(',', env('MOOX_FIREWALL_WHITELIST', ''))),

    // Logo to display on the firewall page, not used yet, must be tied to Moox Brand
    'logo' => env('MOOX_FIREWALL_LOGO', 'img/logo.png'),

    // Firewall title (brand). Keep this in config so it can be overridden globally.
    // Example: "Moox Firewall" in both de/en, so there is no language mix.
    'message' => env('MOOX_FIREWALL_MESSAGE', 'Moox Firewall'),

    // Backdoor allowed?
    'backdoor' => env('MOOX_FIREWALL_BACKDOOR', true),

    // Backdoor bypass token (set a strong token in production)
    'backdoor_token' => env('MOOX_FIREWALL_BACKDOOR_TOKEN'),

    // Backdoor limited to URL
    'backdoor_url' => env('MOOX_FIREWALL_BACKDOOR_URL', '/backdoor'),

    // Show backdoor form inline on blocked URL instead of redirecting
    'inline_challenge' => (bool) env('MOOX_FIREWALL_INLINE_CHALLENGE', true),

    // Firewall page color, currently hex, will be Tailwind color in the future
    'color' => env('MOOX_FIREWALL_COLOR', 'darkblue'),

    // Exclude routes from firewall
    'exclude' => [
        'wilo/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Protect only specific routes (optional)
    |--------------------------------------------------------------------------
    |
    | If empty, the firewall applies to all routes except those listed in
    | `exclude` (and the built-in `wilo/*` bypass).
    |
    | Examples:
    |  - 'admin/*'
    |  - 'api/private/*'
    |
    */
    'protect' => array_values(array_filter(array_map(
        fn (string $value): string => trim($value),
        explode(',', env('MOOX_FIREWALL_PROTECT', '')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Whitelist bypass only for specific routes (optional)
    |--------------------------------------------------------------------------
    |
    | If empty, whitelisted IPs bypass the firewall for all routes.
    | If set, whitelisted IPs bypass only when the request matches one of
    | these patterns.
    |
    */
    'whitelist_allow' => array_values(array_filter(array_map(
        fn (string $value): string => trim($value),
        explode(',', env('MOOX_FIREWALL_WHITELIST_ALLOW', '')),
    ))),

    // Keep backdoor session access for this many minutes
    'session_ttl_minutes' => (int) env('MOOX_FIREWALL_SESSION_TTL_MINUTES', 120),

    // Max invalid backdoor attempts per minute per IP
    'backdoor_rate_limit' => (int) env('MOOX_FIREWALL_BACKDOOR_RATE_LIMIT', 5),

    // Legacy listener kept for backward compatibility/testing.
    // Recommended: keep disabled (middleware is the secure default).
    'legacy_listener' => [
        'enabled' => (bool) env('MOOX_FIREWALL_LEGACY_LISTENER_ENABLED', false),
    ],

    // Register firewall whitelist resource in Filament plugins
    'resource' => [
        'enabled' => (bool) env('MOOX_FIREWALL_RESOURCE_ENABLED', true),
    ],
];
