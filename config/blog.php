<?php

return [
    'resources' => [
        'blog' => [
            'enabled' => true,
            'label' => 'Blog',
            'plural_label' => 'Blogs',
            'navigation_group' => 'Blog Group',
            'navigation_icon' => 'heroicon-o-play',
            'navigation_sort' => 1,
            'navigation_count_badge' => true,
            'resource' => Moox\Blog\Resources\BlogResource::class,
        ],
    ],
    'pruning' => [
        'enabled' => true,
        'retention_days' => 7,
    ],
];
