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

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | The following configuration is done per Filament resource.
    |
    */

    'resources' => [
        'post' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::press.post',
            'plural' => 'trans//core::press.posts',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Expiry table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                /*
                'error' => [
                    'label' => 'trans//core::core.error',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'subject_type',
                            'operator' => '=',
                            'value' => 'Error',
                        ],
                    ],
                ],
                */
            ],
        ],
        'category' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::press.category',
            'plural' => 'trans//core::press.categories',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Expiry table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                /*
                'error' => [
                    'label' => 'trans//core::core.error',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'subject_type',
                            'operator' => '=',
                            'value' => 'Error',
                        ],
                    ],
                ],
                */
            ],
        ],
    ],

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

    'press_navigation_group' => 'trans//core::content.press',
    'system_navigation_group' => 'trans//core::core.system',
    'user_navigation_group' => 'trans//core::core.users',

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

    'press_navigation_sort' => 7900,
    'system_navigation_sort' => 7000,
    'user_navigation_sort' => 6015,

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
    | Press - Moox Roles to WordPress Roles
    |--------------------------------------------------------------------------
    |
    | This array maps the Moox roles to the WordPress roles.
    |
    */

    'moox_roles' => [
        'System Administrator' => 'Administrator',
        'Administrator' => 'Editor',
        'Editor' => 'Editor',
        'Author' => 'Author',
        'Contributor' => 'Contributor',
        'Subscriber' => 'Subscriber',
    ],

    /*
    |--------------------------------------------------------------------------
    | Press - WordPress Roles and Capabilities
    |--------------------------------------------------------------------------
    |
    | This array maps the WordPress roles to the serialized capabilitiy
    | array, that is stored in the usermeta table.
    |
    */

    'wp_roles' => [
        'Administrator' => serialize(['administrator' => true]),
        'Editor' => serialize(['editor' => true]),
        'Author' => serialize(['author' => true]),
        'Contributor' => serialize(['contributor' => true]),
        'Subscriber' => serialize(['subscriber' => true]),
    ],

    'wp_user_levels' => [
        'administrator' => 10,
        'editor' => 7,
        'author' => 2,
        'contributor' => 1,
        'subscriber' => 0,
    ],

    'default_user_attributes' => [
        'user_registered' => now()->toDateTimeString(),
        'user_status' => '0',
        // Todo: suppress errors for first and last name
        //'display_name' => $first_name.' '.$last_name ?? $user_login ?? '',
    ],

    /*
    |--------------------------------------------------------------------------
    | Press - User Meta
    |--------------------------------------------------------------------------
    |
    | These are the user meta keys for the WordPress users. Defined meta
    | keys will be appended to the user model and can be used
    | in the application. Be careful with this.
    |
    */

    'default_user_meta' => [
        'nickname' => $user_login ?? '',
        'first_name' => '',
        'last_name' => '',
        'description' => '',
        'rich_editing' => 'true',
        'comment_shortcuts' => 'false',
        'admin_color' => 'fresh',
        'use_ssl' => '0',
        'show_admin_bar_front' => 'true',
        env('WP_PREFIX', 'wp_').'_capabilities' => serialize([
            'subscriber' => true,
        ]),
        env('WP_PREFIX', 'wp_').'_user_level' => '0',
        'dismissed_wp_pointers' => '',
        env('WP_PREFIX', 'wp_').'_dashboard_quick_press_last_post_id' => '0',
        'mm_sua_attachment_id' => '',

        // locale
        // comment_shortcuts
        // syntax_highlighting

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
