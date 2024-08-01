<?php

return [
    'navigation_sort' => 9001,
    'navigation_label' => 'Moox Expiry',

    'user_model' => \Moox\Press\Models\WpUser::class,
    'default_notified_to' => 1,

    // Disable manual action buttons in UI
    'create_expiry_action' => false,
    'collect_expiries_action' => true,
    'send_summary_action' => true,

    // Jobs for expiries, create custom jobs if needed
    // use and customize CollectExpiries instead of DemoExpiries
    // DemoExpiries is just a job for creating demo data:
    // 'collect_expiries_job' => \Moox\Expiry\Jobs\CollectExpiries::class,
    'collect_expiries_jobs' => [
        \Moox\Expiry\Jobs\CollectExpiries::class,
        // Add more jobs here if needed.
    ],
    'send_summary_job' => \Moox\Expiry\Jobs\SendSummary::class,
    'api' => true,
];
