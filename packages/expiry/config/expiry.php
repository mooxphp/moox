<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Expiry - Navigation Sort
    |--------------------------------------------------------------------------
    |
    | This value is the sort order of the navigation item in the
    | Filament Admin Panel. If you use a bunch of Moox
    | plugins, everything should be in order.
    |
    */

    'navigation_sort' => 7001,

    /*
    |--------------------------------------------------------------------------
    | Expiry - Tabs
    |--------------------------------------------------------------------------
    |
    | Define the tabs for the Expiry table. They are optional, but
    | pretty awesome to filter the table by certain values.
    | You may simply do a 'tabs' => [], to disable them.
    |
    */

    'tabs' => [
        'all' => [
            'label' => 'All',
            'field' => 'expiry_job',
            'value' => '',
            'icon' => 'gmdi-filter-list',
        ],
        'documents' => [
            'label' => 'Documents',
            'field' => 'expiry_job',
            'value' => 'Documents',
            'icon' => 'gmdi-text-snippet',
        ],
        'articles' => [
            'label' => 'Articles',
            'field' => 'expiry_job',
            'value' => 'Articles',
            'icon' => 'gmdi-account-circle',
        ],
        'tasks' => [
            'label' => 'Tasks',
            'field' => 'expiry_job',
            'value' => 'Tasks',
            'icon' => 'gmdi-no-accounts',
        ],
        'no-user' => [
            'label' => 'No Assignee',
            'field' => 'status',
            'value' => 'No Assignee',
            'icon' => 'gmdi-no-accounts',
        ],
        'no-date' => [
            'label' => 'No Expiry Date',
            'field' => 'status',
            'value' => 'No Expiry Date',
            'icon' => 'gmdi-no-accounts',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Expiry - Url Patterns
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
    | Expiry - User Model and default user to notify
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
    | Expiry - Disable actions
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

    /*
    |--------------------------------------------------------------------------
    | Expiry - Jobs
    |--------------------------------------------------------------------------
    |
    | These jobs are used to collect expiries and send summaries.
    |
    */

    'collect_expiries_jobs' => [
        \Moox\Expiry\Jobs\CollectExpiries::class,
        // Add more jobs here if needed.
    ],
    'send_summary_job' => \Moox\Expiry\Jobs\SendSummary::class,

    /*
    |--------------------------------------------------------------------------
    | Expiry - API
    |--------------------------------------------------------------------------
    |
    | Enable or disable the API.
    |
    */

    'api' => true,
];
