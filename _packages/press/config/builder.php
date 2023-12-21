<?php

return [
    'resources' => [
        'builder' => [
            'enabled' => true,
            'label' => 'Builder',
            'plural_label' => 'Builders',
            'navigation_group' => 'Builder Group',
            'navigation_icon' => 'heroicon-o-play',
            'navigation_sort' => 1,
            'navigation_count_badge' => true,
            'resource' => Moox\Builder\Resources\BuilderResource::class,
        ],
    ],
    'pruning' => [
        'enabled' => true,
        'retention_days' => 7,
    ],
];
