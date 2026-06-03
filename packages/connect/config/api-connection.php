<?php

return [
    'single' => 'trans//connect::api-connection.api-connection',
    'plural' => 'trans//connect::api-connection.api-connections',
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
    ],
    'relations' => [],
    'taxonomies' => [],
    'navigation_group' => 'trans//connect::api-connection.navigation_group',

];
