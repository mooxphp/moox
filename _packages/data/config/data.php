<?php

return [
    'resources' => [
        'data' => [
            'enabled' => true,
            'label' => 'Data',
            'plural_label' => 'Datas',
            'navigation_group' => 'Data Group',
            'navigation_icon' => 'heroicon-o-play',
            'navigation_sort' => 1,
            'navigation_count_badge' => true,
            'resource' => Moox\Data\Resources\DataResource::class,
        ],
    ],
    'pruning' => [
        'enabled' => true,
        'retention_days' => 7,
    ],
];
