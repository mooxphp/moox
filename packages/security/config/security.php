<?php

use Illuminate\Validation\Rules\Password;

return [
    'navigation_sort' => 2001,

    'wpModel' => Moox\Press\Models\WpUser::class,

    'auth' => [
        'web' => [
            'username' => 'name',
            'email' => 'email',
            'password' => 'password',
        ],
    ],

    // Using Laravel Password Validation
    'password' => [
        'rules' => Password::min(20)
            ->max(64)
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised(),
    ],

    'password_reset_links' => [
        'model' => App\Models\User::class,
    ],
];
