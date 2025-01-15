<?php

return [
    'single' => 'trans//static-language.static-language',
    'plural' => 'trans//static-language.static-languages',
    'tabs' => [
        'all' => [
            'label' => 'trans//core::core.all',
            'icon' => 'gmdi-filter-list',
            'query' => [],
        ],
        '0' => [
            'label' => 'LTR',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'type',
                    'operator' => '=',
                    'value' => 'LTR',
                ],
            ],
        ],
        '1' => [
            'label' => 'RTL',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'type',
                    'operator' => '=',
                    'value' => 'RTL',
                ],
            ],
        ],
    ],
    'relations' => [],
    'taxonomies' => [],
];
