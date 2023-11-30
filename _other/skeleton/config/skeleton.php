<?php

return [
    'resources' => [
        'skeleton' => [
            'enabled' => true,
            'label' => 'Skeleton',
            'plural_label' => 'Skeletons',
            'navigation_group' => 'Skeleton Group',
            'navigation_icon' => 'heroicon-o-play',
            'navigation_sort' => 1,
            'navigation_count_badge' => true,
            'resource' => Moox\Skeleton\Resources\SkeletonResource::class,
        ],
    ],
    'pruning' => [
        'enabled' => true,
        'retention_days' => 7,
    ],
];
