<?php

return [
    'navigation_sort' => 2001,

    'wpModel' => Moox\Press\Models\WpUser::class,

    'auth' => [
        'web' => [
            'username' => 'name',
            'email' => 'email',
            'password' => 'password',
        ],
        'press' => [
            'username' => 'name',
            'email' => 'email',
            'password' => 'password',
        ],
    ],

    // Using Laravel Password Validation
    'password' => [
        'validation' => [
            'rules' => Illuminate\Validation\Rules\Password::min(20)
                ->max(64)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(),
        ],
        'helperText' => 'Das Passwort muss zwischen 20 und 64 Zeichen lang sein, Groß- und Kleinbuchstaben, Zahlen und Sonderzeichen enthalten.',
    ],

    // The column-name in your user-table
    'mail_recipient_name' => 'name',

    'password_reset_links' => [
        'model' => Moox\User\Models\User::class,
    ],

    'actions' => [
        'bulkactions' => [
            'sendPasswordResetLinkBulkAction' => true,
        ],

    ],
];
