<?php

return [
    'single' => 'trans//connect::api-endpoint.api-endpoint',
    'plural' => 'trans//connect::api-endpoint.api-endpoints',
    'tabs' => [
        'all' => [
            'label' => 'trans//core::core.all',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'deleted_at',
                    'operator' => '=',
                    'value' => null,
                ],

            ],
        ],
        'deleted' => [
            'label' => 'trans//core::core.deleted',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'deleted_at',
                    'operator' => '!=',
                    'value' => null,
                ],
            ],
        ],

        // '1' => [
        //     'label' => 'unused',
        //     'icon' => 'gmdi-filter-list',
        //     'query' => [
        //         [
        //             'field' => 'type',
        //             'operator' => '=',
        //             'value' => 'unused',
        //         ],
        //     ],
        // ],
        // '2' => [
        //     'label' => 'active',
        //     'icon' => 'gmdi-filter-list',
        //     'query' => [
        //         [
        //             'field' => 'type',
        //             'operator' => '=',
        //             'value' => 'active',
        //         ],
        //     ],
        // ],
        // '3' => [
        //     'label' => 'error',
        //     'icon' => 'gmdi-filter-list',
        //     'query' => [
        //         [
        //             'field' => 'type',
        //             'operator' => '=',
        //             'value' => 'error',
        //         ],
        //     ],
        // ],
        // '4' => [
        //     'label' => 'disabled',
        //     'icon' => 'gmdi-filter-list',
        //     'query' => [
        //         [
        //             'field' => 'type',
        //             'operator' => '=',
        //             'value' => 'disabled',
        //         ],
        //     ],
        // ],
    ],
    'relations' => [],
    'taxonomies' => [],
    'navigation_group' => 'trans//connect::api-endpoint.navigation_group',

];
