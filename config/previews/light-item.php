<?php

return [
    'single' => 'trans//previews/light-item.light-item',
    'plural' => 'trans//previews/light-item.light-items',
    'tabs' => [
        'all' => [
            'label' => 'trans//core::core.all',
            'icon' => 'gmdi-filter-list',
            'query' => [
            ],
        ],
        '0' => [
            'label' => 'Post',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'type',
                    'operator' => '=',
                    'value' => 'Post',
                ],
            ],
        ],
        '1' => [
            'label' => 'Page',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'type',
                    'operator' => '=',
                    'value' => 'Page',
                ],
            ],
        ],
    ],
    'relations' => [],
    'taxonomies' => [],
];
