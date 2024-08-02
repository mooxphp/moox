<?php

return [

    /*
    |--------------------------------------------------------------------------
    | User Session - Navigation Sort
    |--------------------------------------------------------------------------
    |
    | This values are the sort order of the navigation items in the
    | Filament Admin Panel. If you use a bunch of Moox
    | plugins, everything should be in order.
    |
    */

    'navigation_sort' => 2001,
    'user_models' => [
        'App Users' => \App\Models\User::class,
        'Moox Users' => \Moox\User\Models\User::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | User Session - Device Model
    |--------------------------------------------------------------------------
    |
    | This is the model used to store the user devices.
    |
    */

    'device_model' => \Moox\UserDevice\Models\UserDevice::class,

    /*
    |--------------------------------------------------------------------------
    | User Session - Expiry
    |--------------------------------------------------------------------------
    |
    | This is under development.
    |
    */

    'session-expiry' => [
        'Default' => 1, // day
        'Whitelisted' => 365, // days
    ],

];
