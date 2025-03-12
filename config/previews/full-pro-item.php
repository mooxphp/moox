<?php

use Moox\Category\Models\Category;
use Moox\Category\Forms\TaxonomyCreateForm;
use Moox\Tag\Models\Tag;

return [
    'single' => 'trans//previews/full-pro-item.full-pro-item',
    'plural' => 'trans//previews/full-pro-item.full-pro-items',
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
    'taxonomies' => [
        'category' => [
            'label' => 'Categories',
            'model' => Category::class,
            'table' => 'categorizables',
            'relationship' => 'categorizable',
            'foreignKey' => 'categorizable_id',
            'relatedKey' => 'category_id',
            'createForm' => TaxonomyCreateForm::class,
            'hierarchical' => true,
        ],

        'tag' => [
            'label' => 'Tags',
            'model' => Tag::class,
            'table' => 'taggables',
            'relationship' => 'taggable',
            'foreignKey' => 'taggable_id',
            'relatedKey' => 'tag_id',
            'createForm' => \Moox\Tag\Forms\TaxonomyCreateForm::class,
            'hierarchical' => false,
        ],

    ],
];
