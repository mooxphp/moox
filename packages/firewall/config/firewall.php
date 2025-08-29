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

    // Backdoor allowed?
    'backdoor' => env('MOOX_FIREWALL_BACKDOOR', true),

    // Backdoor bypass token
    'backdoor_token' => env('MOOX_FIREWALL_BACKDOOR_TOKEN', 'let-me-in'),

    // Backdoor limited to URL
    'backdoor_url' => env('MOOX_FIREWALL_BACKDOOR_URL', '/backdoor'),

    // Firewall page message
    'message' => env('MOOX_FIREWALL_MESSAGE', 'Moox Firewall'),

    // Firewall page description
    'description' => env('MOOX_FIREWALL_DESCRIPTION', 'Please enter your access token to continue.'),

    // Firewall denied message
    'denied_message' => env('MOOX_FIREWALL_DENIED_MESSAGE', 'Access denied. Please contact the IT department.'),

    // Firewall page color, currently hex, will be Tailwind color in the future
    'color' => env('MOOX_FIREWALL_COLOR', 'darkblue'),

    // Exclude routes from firewall
    'exclude' => [
        'api/expiries/*',
    ],
];
