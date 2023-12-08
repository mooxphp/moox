<?php

return [
    'resources' => [
        'core' => [
            'enabled' => true,
            'label' => 'Core',
            'plural_label' => 'Cores',
            'navigation_group' => 'Core Group',
            'navigation_icon' => 'heroicon-o-play',
            'navigation_sort' => 1,
            'navigation_count_badge' => true,
            'resource' => Moox\Core\Resources\CoreResource::class,
        ],
    ],
    'pruning' => [
        'enabled' => true,
        'retention_days' => 7,
    ],
];
