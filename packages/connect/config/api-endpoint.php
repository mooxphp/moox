<?php

return [
    'single' => 'trans//connect::api-endpoint.api-endpoint',
    'plural' => 'trans//connect::api-endpoint.api-endpoints',
    'tabs' => [
        'all' => [
            'label' => 'trans//core::core.all',
            'icon' => 'gmdi-filter-list',
            'query' => [
            ],
        ],
        '0' => [
            'label' => 'new',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'type',
                    'operator' => '=',
                    'value' => 'new',
                ],
            ],
        ],
        '1' => [
            'label' => 'unused',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'type',
                    'operator' => '=',
                    'value' => 'unused',
                ],
            ],
        ],
        '2' => [
            'label' => 'active',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'type',
                    'operator' => '=',
                    'value' => 'active',
                ],
            ],
        ],
        '3' => [
            'label' => 'error',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'type',
                    'operator' => '=',
                    'value' => 'error',
                ],
            ],
        ],
        '4' => [
            'label' => 'disabled',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'type',
                    'operator' => '=',
                    'value' => 'disabled',
                ],
            ],
        ],
    ],
    'relations' => [],
    'taxonomies' => [],
];
