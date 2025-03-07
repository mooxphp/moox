<?php

return [
    'single' => 'trans//connect::api-connection.api-connection',
    'plural' => 'trans//connect::api-connection.api-connections',
    'tabs' => [
        'all' => [
            'label' => 'trans//core::core.all',
            'icon' => 'gmdi-filter-list',
            'query' => [
            ],
        ],
        '0' => [
            'label' => '1',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'type',
                    'operator' => '=',
                    'value' => '1',
                ],
            ],
        ],
        '1' => [
            'label' => '',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'type',
                    'operator' => '=',
                    'value' => '',
                ],
            ],
        ],
    ],
    'relations' => [],
    'taxonomies' => [],
];
