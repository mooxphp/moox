<?php

use Moox\User\Models\User;

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
        'login-link' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::login-link.login-link',
            'plural' => 'trans//core::login-link.login-links',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Resource table. They are optional, but
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
                'documents' => [
                    'label' => 'trans//core::core.documents',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'expiry_job',
                            'operator' => '=',
                            'value' => 'Documents',
                        ],
                    ],
                ],
                */
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation Group
    |--------------------------------------------------------------------------
    |
    | The translatable title of the navigation group in the
    | Filament Admin Panel. Instead of a translatable
    | string, you may also use a simple string.
    |
    */

    'navigation_group' => 'trans//core::user.users',

    /*
    |--------------------------------------------------------------------------
    | Login Link - User Models
    |--------------------------------------------------------------------------
    |
    | Add your user models here. You can add as many as you want.
    |
    */

    'user_models' => [
        // Use class-strings to avoid hard dependencies on optional packages.
        // This must include the model used by the panel's auth guard provider.
        'Users' => User::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Login Link - Expiration
    |--------------------------------------------------------------------------
    |
    | How long should login links remain valid (in minutes)?
    |
    */

    'expiration_minutes' => 60,

    /*
    |--------------------------------------------------------------------------
    | Mail
    |--------------------------------------------------------------------------
    |
    | Optional logo URL/path shown in login-link emails. You may provide
    | an absolute URL or a public path starting with "/". When empty or the
    | file does not exist, the application name (APP_NAME) is shown instead.
    |
    */

    'mail_logo_url' => env('LOGIN_LINK_MAIL_LOGO_URL'),

    /*
    |--------------------------------------------------------------------------
    | Passwordless / Login link (toggle)
    |--------------------------------------------------------------------------
    |
    | Toggle the passwordless login-link flow.
    |
    */

    'passwordless' => [
        'enabled' => env('LOGIN_LINK_PASSWORDLESS_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate limiting (send login link only)
    |--------------------------------------------------------------------------
    |
    | Limits unauthenticated "send magic link" requests on the login page.
    | ip_* caps total attempts per IP; max_attempts caps per IP + email.
    |
    */

    'rate_limit' => [
        'send' => [
            'max_attempts' => (int) env('LOGIN_LINK_SEND_MAX_ATTEMPTS', 5),
            'decay_seconds' => (int) env('LOGIN_LINK_SEND_DECAY_SECONDS', 60),
            'ip_max_attempts' => (int) env('LOGIN_LINK_SEND_IP_MAX_ATTEMPTS', 20),
            'ip_decay_seconds' => (int) env('LOGIN_LINK_SEND_IP_DECAY_SECONDS', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Login page route patterns (middleware)
    |--------------------------------------------------------------------------
    |
    | Used for redeeming legacy links that still point at the login URL with
    | ?loginLink= query parameter.
    |
    */

    'login_route_patterns' => [
        'filament.*.auth.login',
    ],

];
