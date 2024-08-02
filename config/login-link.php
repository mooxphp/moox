<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Login Link - Navigation Sort
    |--------------------------------------------------------------------------
    |
    | This values are the sort order of the navigation items in the
    | Filament Admin Panel. If you use a bunch of Moox
    | plugins, everything should be in order.
    |
    */

    'navigation_sort' => 2001,

    /*
    |--------------------------------------------------------------------------
    | Login Link - User Models
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
    | Login Link - Redirect To
    |--------------------------------------------------------------------------
    |
    | Where should we go after successful login?
    |
    */

    'redirect_to' => '/moox',

];
