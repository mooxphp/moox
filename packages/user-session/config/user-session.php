<?php

return [
    'navigation_sort' => 2001,
    'user_models' => [
        'App Users' => \App\Models\User::class,
        'Moox Users' => \Moox\User\Models\User::class,
    ],
    'device_model' => \Moox\UserDevice\Models\UserDevice::class,
    'session-expiry' => [
        'Default' => 1, // day
        'Whitelisted' => 365, // days
    ],
];
