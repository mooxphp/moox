---
title: Entities
description: Moox entities as domain objects; base entities (Item, Record, Draft, Page, Post, Product, Article, Variant) and relations config.
---

# Entities

Entities are the main domain objects of a Moox application.

Ideally each entity lives in its own package (e.g. Moox Post). A post can power a blog, newsfeed, or similar; it can be wired to other entities and taxonomies.

A single entity is typically implemented as Filament Resources with:

- Config
- Models
- Migrations
- Resource and pages
- Policy
- Factory
- Tests

## Base entities

These base entities are used by the `moox:build` command to generate custom entities:

- **Moox Item** — Simple e.g. log entries, build from scratch
- **Moox Record** — Item with soft delete, taxonomies, relations, modules
- **Moox Draft** — Record with localization, draft, publish

for the CMS Bundle

- **Moox Page** — Draft with page fields, nested set
- **Moox Post** — Draft with post/news fields

for the Shop Bundle

- **Moox Product** — Draft with shop product fields
- **Moox Article** — Single articles for a product
- **Moox Variant** — Variant of article or product
- **Moox Batch** - Batch of variant, article or product
- **Moox Customer** - combines Company, Person, Address and relations

Entities work with other entities, taxonomies, modules, components, and blocks. Wiring is done in configuration.

## Relations

Configure relations between entities in config:

```php
'resources' => [
    'continent' => [
        'hasMany' => [
            'countries' => [
                'table' => 'continents_countries',
            ],
        ],
    ],
    'country' => [
        'hasMany' => [
            'continents' => [
                'table' => 'continents_countries',
            ],
        ],
        'hasMany' => [
            'regions' => [
                'field' => 'regions.country',
            ],
        ],
    ],
    'region' => [
        'belongsTo' => [
            'country' => [
                'field' => 'regions.country',
                'createForm' => RegionCreateForm::class,
            ],
        ],
    ],
],
```

---

title: Configuration
description: Central Moox configuration via Bundles; resources, relations, taxonomies, modules, user models.

---

# Configuration

Configure Moox in one place by using a Bundle.

Translatable strings let you configure all resources, including relations, taxonomies, modules, and the user model.

```php
<?php

return [
    'readonly' => false,

    'resources' => [
        'draft' => [
            'single' => 'trans//draft::draft.draft',
            'plural' => 'trans//draft::draft.drafts',
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
        ],
    ],
    'relations' => [
        'post' => [
            'label' => 'trans//core::core.post',
            'relation' => \Moox\PostRelation\Relations\Post::class,
            // a relation needs to run the relationManager(s)
            'type' => 'hasMany',
        ],
    ],
    'taxonomies' => [
        'category' => [
            'label' => 'trans//core::core.category',
            'model' => \Moox\Category\Models\Category::class,
            'table' => 'categorizables',
            'relationship' => 'categorizable',
            'foreignKey' => 'categorizable_id',
            'relatedKey' => 'category_id',
            'createForm' => \Moox\Category\Forms\TaxonomyCreateForm::class,
            'hierarchical' => true,
        ],
        'tag' => [
            'label' => 'trans//core::core.tag',
            'model' => \Moox\Tag\Models\Tag::class,
            'table' => 'taggables',
            'relationship' => 'taggable',
            'foreignKey' => 'taggable_id',
            'relatedKey' => 'tag_id',
            'createForm' => \Moox\Tag\Forms\TaxonomyCreateForm::class,
            'hierarchical' => false,
        ],
    ],
    'user_models' => [
        \App\Models\User::class => [
            'title_attribute' => 'name',
            'label' => 'App User',
        ],
    ],
    'navigation_group' => 'DEV',
];
```

To translate strings, use the language files published from `moox_core` (e.g. `trans//core::core.all` loads from `common.php` and outputs "All"). Tabs are optional; use `'tabs' => []` to disable them. Navigation group and sort control the Resource in the admin panel.
