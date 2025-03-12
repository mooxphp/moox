<?php

return [
    'single' => 'trans//previews/soft-delete-item.soft-delete-item',
    'plural' => 'trans//previews/soft-delete-item.soft-delete-items',
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
