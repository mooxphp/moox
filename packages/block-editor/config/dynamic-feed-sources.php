<?php

use Moox\BlockEditor\EntityQuery\Mappers\DraftFeedItemMapper;
use Moox\News\Models\News;

return [
    'news' => [
        'enabled' => true,
        'model' => News::class,
        'label' => 'trans//news::news.news',
        'default_view' => 'card',
        'views' => [
            'card' => [
                'label' => 'Card',
                'view' => 'myheco::news.blocks.dynamic-feed.card',
            ],
            'list' => [
                'label' => 'List',
                'view' => 'myheco::news.blocks.dynamic-feed.list',
            ],
        ],
        'filter_schema' => [
            'category_id' => [
                'type' => 'select',
                'label' => 'Category',
                'nullable' => true,
                'apply' => 'taxonomy:category',
                'options_resolver' => 'category',
            ],
        ],
        'sortable_columns' => [
            'published_at' => 'nt.published_at',
            'title' => 'nt.title',
        ],
        'feed_item_mapper' => DraftFeedItemMapper::class,
        'feed_item_mapping' => [
            'untitled_label' => 'trans//news::news.untitled',
            'relations' => [
                'category' => [
                    'type' => 'taxonomy',
                    'output' => 'categories',
                    'label_attribute' => 'title',
                    'eager_load' => 'category.translations',
                ],
                // 'author' => [
                //     'type' => 'translation_relation',
                //     'output' => 'author_name',
                //     'attributes' => ['name', 'title'],
                //     'eager_load' => 'translations.author',
                // ],
                'image' => [
                    'type' => 'attribute',
                    'path' => 'image',
                    'output' => 'image',
                    'resolve_url' => true,
                ],
            ],
        ],
    ],
];
