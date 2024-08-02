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
