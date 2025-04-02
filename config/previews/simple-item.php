<?php

return [
    'single' => 'trans//previews/simple-item.simple-item',
    'plural' => 'trans//previews/simple-item.simple-items',
    'tabs' => [
        'all' => [
            'label' => 'trans//core::core.all',
            'icon' => 'gmdi-filter-list',
            'query' => [
            ],
        ],
        '0' => [
            'label' => 'Post',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'type',
                    'operator' => '=',
                    'value' => 'Post',
                ],
            ],
        ],
        '1' => [
            'label' => 'Page',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'type',
                    'operator' => '=',
                    'value' => 'Page',
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
