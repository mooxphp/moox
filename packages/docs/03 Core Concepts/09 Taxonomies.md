---
title: Taxonomies
description: Configuring taxonomies per resource; Category, Tag, product categories.
---

# Taxonomies

Taxonomies can be configured per resource:

```php
'resources' => [
    'draft' => [
        'taxonomies' => [
            'category' => [
                'label' => 'trans//core::core.category',
                'model' => Category::class,
                'table' => 'categorizables',
                'relationship' => 'categorizable',
                'foreignKey' => 'categorizable_id',
                'relatedKey' => 'category_id',
                'createForm' => TaxonomyCreateForm::class,
                'hierarchical' => true,
                'multiple' => true,
            ],
        ],
    ],
],
```

This connects the model, shows the taxonomy selector in the UI, and can generate (hierarchical) readable URLs for the frontend.

Common taxonomies:

- **Categories** — Hierarchical terms; system taxonomy
- **Tags** — Flat terms; system taxonomy
- **Product categories** — Categories for webshops

install and use as-is or as template using the [Build command]().
