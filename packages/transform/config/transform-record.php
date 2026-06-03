<?php

declare(strict_types=1);

return [
    'single' => 'trans//transform::transform-record.single',
    'plural' => 'trans//transform::transform-record.plural',
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
    'navigation_group' => 'trans//transform::transform-record.navigation_group',
];
