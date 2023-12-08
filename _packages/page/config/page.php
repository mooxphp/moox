<?php

return [
    'resources' => [
        'page' => [
            'enabled' => true,
            'label' => 'Page',
            'plural_label' => 'Pages',
            'navigation_group' => 'Page Group',
            'navigation_icon' => 'heroicon-o-play',
            'navigation_sort' => 1,
            'navigation_count_badge' => true,
            'resource' => Moox\Page\Resources\PageResource::class,
        ],
    ],
    'pruning' => [
        'enabled' => true,
        'retention_days' => 7,
    ],
];
