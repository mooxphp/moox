<?php

return [
    'single' => 'trans//entities/static-country.static_country',
    'plural' => 'trans//entities/static-country.static_countries',
    'tabs' => [
        'all' => [
            'label' => 'trans//core::core.all',
            'icon' => 'gmdi-filter-list',
            'query' => [],
        ],
        '0' => [
            'label' => 'New',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'status',
                    'operator' => '=',
                    'value' => 'New',
                ],
            ],
        ],
        '1' => [
            'label' => 'Open',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'status',
                    'operator' => '=',
                    'value' => 'Open',
                ],
            ],
        ],
        '2' => [
            'label' => 'Pending',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'status',
                    'operator' => '=',
                    'value' => 'Pending',
                ],
            ],
        ],
        '3' => [
            'label' => 'Closed',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'status',
                    'operator' => '=',
                    'value' => 'Closed',
                ],
            ],
        ],
    ],
    'relations' => [],
    'taxonomies' => [],
];
