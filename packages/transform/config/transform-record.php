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
        'failed' => [
            'label' => 'trans//core::core.failed',
            'icon' => 'gmdi-error',
            'query' => [
                [
                    'field' => 'status',
                    'operator' => '=',
                    'value' => 'failed',
               
                ],
            ],
        ],
        'updated' => [
            'label' => 'trans//transform::fields.updated',
            'icon' => 'gmdi-update',
            'query' => [
                [
                    'field' => 'status',
                    'operator' => '=',
                    'value' => 'updated',
               
                ],
            ],
        ],
        'pending' => [
            'label' => 'trans//transform::fields.pending',
            'icon' => 'gmdi-pending',
            'query' => [
                [
                    'field' => 'status',
                    'operator' => '=',
                    'value' => 'pending',
               
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
    'destination_resources' => 'config://transform.destination_resources',
    'navigation_group' => 'trans//transform::transform-record.navigation_group',
];
