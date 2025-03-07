<?php

return [
    'single' => 'trans//connect::api-log.api-log',
    'plural' => 'trans//connect::api-log.api-logs',
    'tabs' => [
        'all' => [
            'label' => 'trans//core::core.all',
            'icon' => 'gmdi-filter-list',
            'query' => [
            ],
        ],
        '0' => [
            'label' => 'CRON',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'type',
                    'operator' => '=',
                    'value' => 'CRON',
                ],
            ],
        ],
        '1' => [
            'label' => 'USER',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'type',
                    'operator' => '=',
                    'value' => 'USER',
                ],
            ],
        ],
        '2' => [
            'label' => 'WEBHOOK',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'type',
                    'operator' => '=',
                    'value' => 'WEBHOOK',
                ],
            ],
        ],
        '3' => [
            'label' => 'SYSTEM',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'type',
                    'operator' => '=',
                    'value' => 'SYSTEM',
                ],
            ],
        ],
    ],
    'relations' => [],
    'taxonomies' => [],
];
