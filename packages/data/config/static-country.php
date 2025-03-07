<?php

return [
    'single' => 'trans//data::static-country.static_country',
    'plural' => 'trans//data::static-country.static_countries',
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
