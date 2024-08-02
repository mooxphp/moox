<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Jobs - Navigation Sort
    |--------------------------------------------------------------------------
    |
    | This value is the sort order of the navigation item in the
    | Filament Admin Panel. If you use a bunch of Moox
    | plugins, everything should be in order.
    |
    */
    'navigation_sort' => 5001,

    /*
    |--------------------------------------------------------------------------
    | Jobs - Resources
    |--------------------------------------------------------------------------
    |
    | This configuration needs to be updated soon ...
    |
    */
    'resources' => [
        'jobs' => [
            'enabled' => true,
            'label' => 'Job',
            'plural_label' => 'Jobs',
            'navigation_group' => 'Job manager',
            'navigation_icon' => 'heroicon-o-play',
            'navigation_sort' => 5001,
            'navigation_count_badge' => true,
            'resource' => Moox\Jobs\Resources\JobsResource::class,
        ],
        'jobs_waiting' => [
            'enabled' => true,
            'label' => 'Job waiting',
            'plural_label' => 'Jobs waiting',
            'navigation_group' => 'Job manager',
            'navigation_icon' => 'heroicon-o-pause',
            'navigation_sort' => 5002,
            'navigation_count_badge' => true,
            'resource' => Moox\Jobs\Resources\JobsWaitingResource::class,
        ],
        'failed_jobs' => [
            'enabled' => true,
            'label' => 'Failed Job',
            'plural_label' => 'Failed Jobs',
            'navigation_group' => 'Job manager',
            'navigation_icon' => 'heroicon-o-exclamation-triangle',
            'navigation_sort' => 5003,
            'navigation_count_badge' => true,
            'resource' => Moox\Jobs\Resources\JobsFailedResource::class,
        ],
        'job_batches' => [
            'enabled' => true,
            'label' => 'Job Batch',
            'plural_label' => 'Job Batches',
            'navigation_group' => 'Job manager',
            'navigation_icon' => 'heroicon-o-inbox-stack',
            'navigation_sort' => 5004,
            'navigation_count_badge' => true,
            'resource' => Moox\Jobs\Resources\JobBatchesResource::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Jobs - Pruning
    |--------------------------------------------------------------------------
    |
    | This configuration is used to enable or disable pruning of old jobs.
    |
    */
    'pruning' => [
        'enabled' => true,
        'retention_days' => 7,
    ],
];
