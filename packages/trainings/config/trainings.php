<?php

return [
    'navigation_sort' => 2001,

    // Wire with one or more user models
    'user_models' => [
        'App Users' => \App\Models\User::class,
    ],

    // Disable manual action buttons in UI
    // and queue the provided jobs instead
    'create_trainings_action' => true,
    'training_invitations_collect_action' => true,
    'training_invitations_send_action' => true,
];
