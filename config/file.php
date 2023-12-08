<?php

return [
    'resources' => [
        'file' => [
            'enabled' => true,
            'label' => 'File',
            'plural_label' => 'Files',
            'navigation_group' => 'File Group',
            'navigation_icon' => 'heroicon-o-play',
            'navigation_sort' => 1,
            'navigation_count_badge' => true,
            'resource' => Moox\File\Resources\FileResource::class,
        ],
    ],
    'pruning' => [
        'enabled' => true,
        'retention_days' => 7,
    ],
];
