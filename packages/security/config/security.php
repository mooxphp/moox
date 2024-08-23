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
        'security' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::security.security',
            'plural' => 'trans//core::security.securitys',

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
    | Navigation Sort
    |--------------------------------------------------------------------------
    |
    | This values are the sort order of the navigation items in the
    | Filament Admin Panel. If you use a bunch of Moox
    | plugins, everything should be in order.
    |
    */

    'navigation_sort' => 6600,

    /*
    |--------------------------------------------------------------------------
    | WordPress User Model
    |--------------------------------------------------------------------------
    |
    | If you use a custom WordPress User Model, you can define it here.
    | We already provide a default model for WordPress users.
    | If you don't have Press installed, ignore this.
    |
    */

    'wpModel' => Moox\Press\Models\WpUser::class,

    /*
    |--------------------------------------------------------------------------
    | Auth guards
    |--------------------------------------------------------------------------
    |
    | Define the columns for the username, email and password for the
    | different guards. This is necessary for the login process
    | to allow login with username or email address.
    |
    */

    'auth' => [
        'web' => [
            'username' => 'name',
            'email' => 'email',
            'password' => 'password',
        ],
        'press' => [
            'username' => 'name',
            'email' => 'email',
            'password' => 'password',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Validation
    |--------------------------------------------------------------------------
    |
    | Define the password validation rules for your application.
    | If you want to be hacked pretty soon, you can disable
    | the password validation by emptying the rules.
    |
    */

    'password' => [
        'validation' => [
            'rules' => Illuminate\Validation\Rules\Password::min(20)
                ->max(64)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(),
        ],
        'helperText' => 'Das Passwort muss zwischen 20 und 64 Zeichen lang sein, Groß- und Kleinbuchstaben, Zahlen und Sonderzeichen enthalten.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Recipient Name
    |--------------------------------------------------------------------------
    |
    | For the mail notifications, you can define the column name
    | of the recipient name here. This is necessary for the
    | mail notifications to work properly.
    |
    */

    'mail_recipient_name' => 'name',

    /*
    |--------------------------------------------------------------------------
    | Class for Password Reset Job
    |--------------------------------------------------------------------------
    |
    | This is not implemented yet. But you can define the class and
    | the method for the password reset job here.
    |
    */

    'password_reset_links' => [
        'model' => Moox\User\Models\User::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Enable Reset Bulk Action
    |--------------------------------------------------------------------------
    |
    | You can enable or disable the bulk action for sending password
    | reset links in Moox User and Moox Press User. If you
    | use a custom user model, you have to implement.
    |
    */

    'actions' => [
        'bulkactions' => [
            'sendPasswordResetLinkBulkAction' => true,
        ],

    ],
];
