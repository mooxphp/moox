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
    | Navigation Sort
    |--------------------------------------------------------------------------
    |
    | This value is the sort order of the navigation item in the
    | Filament Admin Panel. If you use a bunch of Moox
    | plugins, everything should be in order.
    |
    */

    'navigation_sort' => 1100,

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

    'user_model' => \Moox\Press\Models\WpUser::class,
    'default_notified_to' => 1,

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
    'collect_expiries_action' => true,
    'send_summary_action' => true,
    'set_date_action' => true,

    /*
    |--------------------------------------------------------------------------
    | Turnus
    |--------------------------------------------------------------------------
    |
    | Define the turnus options for the Expiry table.
    |
    */

    'turnus_options' => [
        'jährlich' => 365,
        'halbjährlich' => 182,
        'quartalsweise' => 90,
        'monatlich' => 30,
        'wöchentlich' => 7,
        'ohne_festen_turnus' => 0,
    ],

    'helper_text_datetime' => 'Falls kein Turnus festgelegt ist, muss das Ablaufdatum manuell gesetzt werden.',
    'after_now' => 'Das Ablaufdatum muss in der Zukunft liegen.',

    /*
    |--------------------------------------------------------------------------
    | Jobs
    |--------------------------------------------------------------------------
    |
    | These jobs are used to collect expiries.
    | You can add more jobs here if needed.
    |
    */

    // TODO: Siehe expiry package
    'collect_expiries_jobs' => [
        //        App\Jobs\GetExpiredWikiPostsJob::class,
        //        App\Jobs\GetExpiredWikiDocsJob::class,
        // Add more jobs here if needed.
    ],
    //    'send_summary_job' => \Moox\Expiry\Jobs\SendSummary::class,

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
