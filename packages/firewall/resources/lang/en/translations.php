<?php

return [
    'message' => 'Moox Firewall',
    'description' => 'Please enter your access token to continue.',
    'denied_message' => 'Access denied. Please contact the IT department.',

    'backdoor_title' => 'Moox Firewall',
    'backdoor_continue' => 'Continue',
    'backdoor_placeholder' => 'Enter your access token',

    'error_invalid_token' => 'Invalid token. Please try again.',
    'error_too_many_attempts' => 'Too many attempts. Please wait a minute and try again.',

    'resource' => [
        'navigation_label' => 'Firewall whitelist',
        'navigation_group' => 'Security',

        'ip_address' => 'IP address',
        'label' => 'Name',
        'active' => 'Active',
        'allow_all_routes' => 'Allow all protected routes',
        'all_routes' => 'All routes',
        'allowed_routes' => 'Allowed routes (patterns)',
        'allowed_routes_hint' => 'Pick a wildcard like "admin/*" from the list, or type to search and select specific routes (e.g. "admin/users"). Patterns must match Laravel Request::is.',
        'allowed_routes_ignored' => 'Allowed routes are ignored because "Allow all protected routes" is enabled.',
        'updated' => 'Updated',
    ],
];
