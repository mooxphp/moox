<?php

return [
    'navigation_sort' => 1901,

    'wordpress_path' => env('WP_PATH', '/public/wp'),
    'wordpress_prefix' => env('WP_PREFIX', 'wp_'),
    'wordpress_slug' => env('WP_SLUG', '/wp'),

    'ip_whitelist' => env('IP_WHITELIST', ''),

    'lock_wordpress' => env('LOCK_WP', false),
    'auth_wordpress' => env('AUTH_WP', false),
    'redirect_index' => env('REDIRECT_INDEX', false),
    'redirect_to_wp' => env('REDIRECT_TO_WP', false),
    'redirect_login' => env('REDIRECT_LOGIN', false),
    'redirect_logout' => env('REDIRECT_LOGOUT', false),
    'redirect_editor' => env('REDIRECT_EDITOR', false),

    'enable_forgot_password' => env('FORGOT_PASSWORD', true),
    'enable_registration' => env('REGISTRATION', false),
    'enable_mfa' => env('ENABLE_MFA', false),

    'user_capabilities' => [
        'Subscriber' => 'a:1:{s:10:"subscriber";b:1;}',
        'Administrator' => 'a:1:{s:13:"administrator";b:1;}',
    ],

    'default_user_meta' => [
        'nickname' => 'user_login',
        'first_name' => '',
        'rich_edit' => 'true',
        'capabilities' => 'Subscriber',
        'mm_sua_attachment_id' => '',
    ],

    'use_api' => true,
    'entities' => [
        'wp_users' => [
            'api' => [
                'enabled' => true,
                'public' => true, // false for private, true for public
                'auth_type' => 'platform', // 'platform' for platform tokens or 'sanctum' for user-tied tokens
                'route_only' => ['index', 'show', 'create', 'destroy'],
            ],
        ],
    ],
];
