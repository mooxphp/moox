<?php

return [
    'resources' => [
        'press' => [
            'enabled' => true,
            'label' => 'Press',
            'plural_label' => 'Presss',
            'navigation_group' => 'Press Group',
            'navigation_icon' => 'heroicon-o-play',
            'navigation_sort' => 1,
            'navigation_count_badge' => true,
            'resource' => Moox\Press\Resources\PressResource::class,
        ],
    ],
    'pruning' => [
        'enabled' => true,
        'retention_days' => 7,
    ],
];
