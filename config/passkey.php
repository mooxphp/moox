<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Passkey - Navigation Sort
    |--------------------------------------------------------------------------
    |
    | This value is the sort order of the navigation item in the
    | Filament Admin Panel. If you use a bunch of Moox
    | plugins, everything should be in order.
    |
    */

    'navigation_sort' => 2001,

    /*
    |--------------------------------------------------------------------------
    | Passkey - User Models
    |--------------------------------------------------------------------------
    |
    | Add your user models here. You can add as many as you want.
    |
    */

    'user_models' => [
        'App Users' => \App\Models\User::class,
        'Moox Users' => \Moox\User\Models\User::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Passkey - Device Model
    |--------------------------------------------------------------------------
    |
    | This is the model used to store the user devices.
    |
    */

    'device_model' => \Moox\UserDevice\Models\UserDevice::class,

];
