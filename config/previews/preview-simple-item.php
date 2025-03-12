<?php

return [
    'single' => 'trans//previews/preview-simple-item.preview-simple-item',
    'plural' => 'trans//previews/preview-simple-item.simple-items',
    'tabs' => [
        'all' => [
            'label' => 'trans//core::core.all',
            'icon' => 'gmdi-filter-list',
            'query' => [
            ],
        ],
        'probably' => [
            'label' => 'Probably',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'status',
                    'operator' => '=',
                    'value' => 'Probably',
                ],
            ],
        ],
        'never' => [
            'label' => 'Never',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'status',
                    'operator' => '=',
                    'value' => 'Never',
                ],
            ],
        ],
        'done' => [
            'label' => 'Done',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'status',
                    'operator' => '=',
                    'value' => 'Done',
                ],
            ],
        ],
        'maybe' => [
            'label' => 'Maybe',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'status',
                    'operator' => '=',
                    'value' => 'Maybe',
                ],
            ],
        ],
    ],
    'relations' => [],
    'taxonomies' => [
        'category' => [
            'label' => 'Categories',
            'model' => \Moox\Category\Models\Category::class,
            'table' => 'categorizables',
            'relationship' => 'categorizable',
            'foreignKey' => 'categorizable_id',
            'relatedKey' => 'category_id',
            'createForm' => \Moox\Category\Forms\TaxonomyCreateForm::class,
            'hierarchical' => true,
        ],

        'tag' => [
            'label' => 'Tags',
            'model' => \Moox\Tag\Models\Tag::class,
            'table' => 'taggables',
            'relationship' => 'taggable',
            'foreignKey' => 'taggable_id',
            'relatedKey' => 'tag_id',
            'createForm' => \Moox\Tag\Forms\TaxonomyCreateForm::class,
            'hierarchical' => false,
        ],

    ],
];
