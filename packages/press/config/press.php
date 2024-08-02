<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Press - Navigation Sort
    |--------------------------------------------------------------------------
    |
    | This values are the sort order of the navigation items in the
    | Filament Admin Panel. If you use a bunch of Moox
    | plugins, everything should be in order.
    |
    */

    'press_navigation_sort' => 1201,
    'system_navigation_sort' => 1201,
    'user_navigation_sort' => 1201,

    /*
    |--------------------------------------------------------------------------
    | Press - Navigation Group
    |--------------------------------------------------------------------------
    |
    | This values are for grouping the navigation items into the
    | right group in the Filament Admin Panel. By default,
    | everything we use three Moox-compatible groups.
    |
    */

    'press_navigation_group' => 'Press',
    'system_navigation_group' => 'Press System',
    'user_navigation_group' => 'User management',

    /*
    |--------------------------------------------------------------------------
    | Press - WordPress Path
    |--------------------------------------------------------------------------
    |
    | Set up the path, prefix, and slug for your WordPress installation.
    | You may have a simple copy of WordPress or use Composer.
    | In either way, you need PHPdotenv to use the .env
    |
    */

    'wordpress_path' => env('WP_PATH', '/public/wp'),

    /*
    |--------------------------------------------------------------------------
    | Press - WordPress Prefix
    |--------------------------------------------------------------------------
    |
    | Set up the table prefix for your WordPress installation.
    | It defaults to wp_ but specially when implementing a
    | WordPress multisite, you may want to change it.
    |
    */

    'wordpress_prefix' => env('WP_PREFIX', 'wp_'),

    /*
    |--------------------------------------------------------------------------
    | Press - WordPress Slug
    |--------------------------------------------------------------------------
    |
    | Set up the URL slug for your WordPress installation.
    | Depending on your setup, your frontend can be
    | redirected to the WordPress installation.
    |
    */

    'wordpress_slug' => env('WP_SLUG', '/wp'),

    /*
    |--------------------------------------------------------------------------
    | Press - WordPress Auth
    |--------------------------------------------------------------------------
    |
    | Set auth_wordpress to true to use Laravel for authentication of
    | your WordPress users in your Laravel application.
    | You need the moox_press WordPress plugin.
    |
    */

    'auth_wordpress' => env('AUTH_WP', false),

    /*
    |--------------------------------------------------------------------------
    | Press - WordPress Lock
    |--------------------------------------------------------------------------
    |
    | Set lock_wordpress to true to bring your complete WordPress
    | behind the Login, that can be either the WordPress login
    | or the Laravel login, depending on auth_wordpress.
    |
    */

    'lock_wordpress' => env('LOCK_WP', false),

    /*
    |--------------------------------------------------------------------------
    | Press - WordPress Redirects
    |--------------------------------------------------------------------------
    |
    | Set up the redirects for your WordPress installation.
    | index for redirecting all requests to WordPress,
    | to_wp for redirecting to WP Admin after login.
    |
    */

    'redirect_index' => env('REDIRECT_INDEX', false),
    'redirect_to_wp' => env('REDIRECT_TO_WP', false),
    'redirect_login' => env('REDIRECT_LOGIN', false),
    'redirect_logout' => env('REDIRECT_LOGOUT', false),
    'redirect_editor' => env('REDIRECT_EDITOR', false),

    /*
    |--------------------------------------------------------------------------
    | Press - Security
    |--------------------------------------------------------------------------
    |
    | This will probably move to Moox Security soon.
    |
    */

    'enable_forgot_password' => env('FORGOT_PASSWORD', true),
    'enable_registration' => env('REGISTRATION', false),
    'enable_mfa' => env('ENABLE_MFA', false),

    /*
    |--------------------------------------------------------------------------
    | Press - WordPress User Capabilities and Meta
    |--------------------------------------------------------------------------
    |
    | This is currently under hard construction ;-)
    |
    */

    'user_capabilities' => [
        'Subscriber' => 'a:1:{s:10:"subscriber";b:1;}',
        'Administrator' => 'a:1:{s:13:"administrator";b:1;}',
        'Editor' => 'a:1:{s:6:"editor";b:1;}',
        'Author' => 'a:1:{s:6:"author";b:1;}',
    ],

    'default_user_meta' => [
        'nickname' => 'user_login',
        'first_name' => '',
        'rich_edit' => 'true',
        'capabilities' => 'Subscriber',
        'mm_sua_attachment_id' => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | Press - API
    |--------------------------------------------------------------------------
    |
    | Enable or disable the API and configure all entities.
    | Public or secured by platform or sanctum.
    | Available at /api/{entity}
    |
    */

    'use_api' => true,
    'entities' => [
        'wp_users' => [
            'api' => [
                'enabled' => true,
                'public' => false, // false for private, true for public
                'auth_type' => 'platform', // 'platform' for platform tokens or 'sanctum' for user-tied tokens
                'route_only' => ['index', 'show', 'store', 'destroy', 'update'],
            ],
        ],
    ],
];
