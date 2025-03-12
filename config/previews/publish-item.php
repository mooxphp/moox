<?php

return [
    'single' => 'trans//previews/publish-item.publish-item',
    'plural' => 'trans//previews/publish-item.publish-items',
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
        'published' => [
            'label' => 'trans//core::core.published',
            'icon' => 'gmdi-check-circle',
            'query' => [
                [
                    'field' => 'publish_at',
                    'operator' => '<=',
                    'value' => 'now()',
                ],
            ],
        ],
        'scheduled' => [
            'label' => 'trans//core::core.scheduled',
            'icon' => 'gmdi-schedule',
            'query' => [
                [
                    'field' => 'publish_at',
                    'operator' => '>',
                    'value' => 'now()',
                ],
            ],
        ],
        'draft' => [
            'label' => 'trans//core::core.draft',
            'icon' => 'gmdi-text-snippet',
            'query' => [
                [
                    'field' => 'published_at',
                    'operator' => '=',
                    'value' => null,
                ],
            ],
        ],
        'deleted' => [
            'label' => 'trans//core::core.deleted',
            'icon' => 'gmdi-delete',
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
];
