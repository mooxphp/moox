# Moox Entities

- Frontend - Entity, Components, Theme, Rendering (Evtl. Moox Renderer, dann könnte es später auch einen React Renderer geben?)
- Build
- Nächste Entities die wir brauchen
- IsTaxonomy? Von welcher Klasse kann ich das ermitteln? TaxonomyCreateForm?
- Features? Same... BaseClass? Or SP!?
- Modules? Same... BaseClass? Or SP!?

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Taxonomies (flat or nested)
    |--------------------------------------------------------------------------
    */
    'taxonomies' => ['tags', 'categories'],

    /*
    |--------------------------------------------------------------------------
    | Features (added via Traits)
    |--------------------------------------------------------------------------
    */
    'features' => ['comments', 'audit'],

    /*
    |--------------------------------------------------------------------------
    | Relations (always polymorphic, all types supported)
    |--------------------------------------------------------------------------
    |
    | Supported types: belongsTo, hasOne, hasMany, belongsToMany
    | model:           The related model class
    | morph_type:      Optional name for polymorphic type
    | pivot:           Pivot table name (for *ToMany relations)
    | pivot_fields:    Custom fields in the pivot table
    | display_field:   Used in UI components (e.g. Select, MultiSelect)
    | validation:      Laravel-style validation rules (e.g. 'required', 'nullable')
    | read_only:       Field is shown but read only
    | hidden:          Relation is done programatically, no UI for that
    | relation_manager: Whether to expose this relation via Filament UI
    |
    */

    'relations' => [

        // Has one: one customer record (still handled polymorphically internally)
        'customer' => [
            'type' => 'hasOne',
            'model' => App\Models\Customer::class,
            'relation_manager' => false, // default: true
        ],

        // Has many: vendors
        'vendors' => [
            'type' => 'hasMany',
            'model' => App\Models\PostTranslation::class,
            'display_field' => 'locale', // optional
            'relation_manager' => true,
        ],

        // Belongs to: one user
        'author' => [
            'type' => 'belongsTo',
            'model' => App\Models\User::class,
            'display_field' => 'name',
            'validation' => 'required',
            'read_only' => true,
        ],

        // Belongs to: any (Polymorph)
        'owner' => [
            'type' => 'belongsTo',
            'display_field' => 'name',
            'validation' => 'nullable',
          	'hidden' => true,
        ],

        // Belongs to many: media
        'media' => [
            'type' => 'belongsToMany',
            'model' => App\Models\Media::class,
            'pivot' => 'media_relations',
            'pivot_fields' => ['order'],
            'morph_type' => 'mediable', // normally not needed
            'relation_manager' => true,
            'display_field' => 'filename',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Modules (UI/logic extensions, shown as tabs or sections)
    |--------------------------------------------------------------------------
    */
    'modules' => [
        'seo',
        'attachments',
    ],
];
```

```bash
php artisan moox:wire
```

Prompts

```
Which entity do you want to wire?
[x] All
[ ] Post
[ ] Page
[ ] News

What do you want to wire for Post?
[x] Taxonomies
[ ] Relations
[ ] Features
[ ] Modules

What Taxonomies should be attached to Post?
[x] Category
[ ] Tag
```

We need to create the classes that wire the models, show the fields, relation managers. That is already done for taxonomies, they have a create form.

1. All entities are based on Base Entities in Moox Core

2. Those base entitites already use the needed traits

3. Those traits implement the relations

4. They care for showing the fields, relation managers etc, when config says

5. There is no PHP-code generated at runtime, never (except publishing configs)
