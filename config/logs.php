<?php

return [
    'resources' => [
        'logs' => [
            'enabled' => true,
            'label' => 'Logs',
            'plural_label' => 'Logss',
            'navigation_group' => 'Logs Group',
            'navigation_icon' => 'heroicon-o-play',
            'navigation_sort' => 1,
            'navigation_count_badge' => true,
            'resource' => Moox\Logs\Resources\LogsResource::class,
        ],
    ],
    'pruning' => [
        'enabled' => true,
        'retention_days' => 7,
    ],
];
