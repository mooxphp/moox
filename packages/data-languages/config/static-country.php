<?php

return [
    'single' => 'trans//static-country.static-country',
    'plural' => 'trans//static-country.static-countries',
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
                    'field' => 'type',
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
                    'field' => 'type',
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
                    'field' => 'type',
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
                    'field' => 'type',
                    'operator' => '=',
                    'value' => 'Closed',
                ],
            ],
        ],
    ],
    'relations' => [],
    'taxonomies' => [],
];
