<?php

return [
    'resources' => [
        'user' => [
            'enabled' => true,
            'label' => 'User',
            'plural_label' => 'Users',
            'navigation_group' => 'User Group',
            'navigation_icon' => 'heroicon-o-play',
            'navigation_sort' => 1,
            'navigation_count_badge' => true,
            'resource' => Moox\User\Resources\UserResource::class,
        ],
    ],
    'pruning' => [
        'enabled' => true,
        'retention_days' => 7,
    ],
];
