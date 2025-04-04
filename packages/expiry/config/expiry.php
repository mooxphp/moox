<?php

use Moox\Expiry\Actions\CustomExpiryAction;
use Moox\Expiry\Jobs\CollectExpiries;
use Moox\Press\Models\WpUser;

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
        'expiry' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::expiry.expiry',
            'plural' => 'trans//core::expiry.expiries',

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
                    'visible' => rtrim((string) env('APP_URL'), '/') == config('app.url'),
                ],
                'articles' => [
                    'label' => 'trans//core::core.articles',
                    'icon' => 'gmdi-account-circle',
                    'query' => [
                        [
                            'field' => 'expiry_job',
                            'operator' => '=',
                            'value' => 'Articles',
                        ],
                    ],
                    'visible' => rtrim((string) env('APP_URL'), '/') == config('app.url'),
                ],
                'tasks' => [
                    'label' => 'trans//core::core.tasks',
                    'icon' => 'gmdi-no-accounts',
                    'query' => [
                        [
                            'field' => 'expiry_job',
                            'operator' => '=',
                            'value' => 'Tasks',
                        ],
                    ],
                    'visible' => rtrim((string) env('APP_URL'), '/') == config('app.url'),
                ],
                'no-user' => [
                    'label' => 'trans//core::expiry.no_assignee',
                    'icon' => 'gmdi-no-accounts',
                    'query' => [
                        [
                            'field' => 'status',
                            'operator' => '=',
                            'value' => 'No assignee',
                        ],
                    ],
                    'visible' => rtrim((string) env('APP_URL'), '/') == config('app.url'),
                ],
                'no-date' => [
                    'label' => 'trans//core::expiry.no_expiry_date',
                    'icon' => 'gmdi-no-accounts',
                    'query' => [
                        [
                            'field' => 'status',
                            'operator' => '=',
                            'value' => 'No expiry date',
                        ],
                    ],
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

    'navigation_group' => 'trans//core::core.main',

    /*
    |--------------------------------------------------------------------------
    | Url Patterns
    |--------------------------------------------------------------------------
    |
    | Define the url patterns for the Expiry table. They are optional, but
    | pretty awesome to point to individual urls. Below are examples.
    | Don't forget to enable the feature, if you want to use it.
    |
    */

    'url_patterns' => [
        'enabled' => false,
        'patterns' => [
            'Documents' => '/#documents',
            'Articles' => '/#articles',
            'Tasks' => '/#tasks',
            'default' => '',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Model and default user to notify
    |--------------------------------------------------------------------------
    |
    | Bring your own user model, or use the default one
    | and set the default user to notify.
    |
    */

    'user_model' => WpUser::class,
    'default_notified_to' => 1,

    /*
    |--------------------------------------------------------------------------
    | Escalation Settings
    |--------------------------------------------------------------------------
    | Configure notifications for escalations.
    | Define timing and recipients.
    | Set the dashboard path.
    */

    'send-escalation' => env('EXPIRY_SEND_ESCALATION', false),
    'send-escalation-days-before' => env('EXPIRY_SEND_ESCALATION_DAYS_BEFORE', 7),
    'send-escalation-copy' => env('EXPIRY_SEND_ESCALATION_COPY', 'admin@moox.com'),
    'panel_path' => env('EXPIRY_PANEL_PATH', 'moox'),
    'logo_url' => env('LOGO_URL', 'https://moox.org/img/logo.png'),

    /*
    |--------------------------------------------------------------------------
    | Disable actions
    |--------------------------------------------------------------------------
    |
    | You can disable some action buttons in the admin panel.
    | These actions are still available via the API
    | or by using the included jobs.
    |
    */

    'create_expiry_action' => false,
    'collect_expiries_action' => false,
    'send_summary_action' => false,

    /*
    |--------------------------------------------------------------------------
    | Cycle
    |--------------------------------------------------------------------------
    |
    | Define the cycle options for the Expiry table.
    |
    */

    'cycle_options' => [
        'yearly' => 365,
        'half_yearly' => 182,
        'quarterly' => 90,
        'monthly' => 30,
        'weekly' => 7,
        'no_fixed_cycle' => 0,
    ],

    'helper_text_datetime' => 'trans//core::expiry.no_expiry_set',
    'after_now' => 'trans//core::expiry.expiry_in_future',

    /*
    |--------------------------------------------------------------------------
    | Jobs
    |--------------------------------------------------------------------------
    |
    | These jobs are used to collect expiries.
    | You can add more jobs here if needed.
    |
    */

    'collect_expiries_jobs' => [
        CollectExpiries::class,
        // Add more jobs here if needed.
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Expiry Action
    |--------------------------------------------------------------------------
    |
    | Here you can define a custom expiry action. This action is used
    | to set the expiry date or do something else with the expired
    | record. It is shown in the Expiry Resource list table.
    |
    */

    'expiry_action' => CustomExpiryAction::class,
    'expiry_action_enable' => false,
    'expiry_action_name' => 'Custom Expiry Action',
    'expiry_view_action_color' => 'primary',

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    |
    | Enable or disable the API.
    |
    */

    'api' => true,
];
