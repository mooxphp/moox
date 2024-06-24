<?php

return [
    'navigation_sort' => 2001,
    'expiration_time' => 24, // hours
    'user_models' => [
        'App Users' => \App\Models\User::class,
        'Moox Users' => \Moox\User\Models\User::class,
    ],
    'redirect_to' => '/moox',
];
