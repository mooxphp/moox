## Entities:

- Record - Moox Record - copy from there
- Domain - Moox Domains
- Theme - Moox Themes
- Frontend - Moox Frontend

## Record

Record is between Item and Draft, it adds features like soft delete and auditing:

- Title with Slug
- Simple Status
- Relations
- Content (RT)
- Data (Json)
- created_at
- created_by
- created_by_type
- updated_at
- updated_by
- updated_by_type
- deleted_at
- deleted_by
- deleted_by_type

We need to reduce Item, we need to polish Draft.

## Domain

- id = 1
- fqdn = fully qualified domain name (moox.org, xyz.moox.de)
- active = bool, default true
- force_ssl = bool, default true
- dns_wildcard = bool, default false
- internal_note = some words
- domain_settings = json (for DNS, SSL etc.)
- created_at
- created_by
- created_by_type
- updated_at
- updated_by
- updated_by_type
- deleted_at
- deleted_by
- deleted_by_type

## Frontend

- id = 1
- name = heco
- slug = not sure how we use it
- parent_frontend_id = NULL = Master, otherwise Stage
- stage = ENUM: live, staging, dev, local, restore
- theme_id = used theme, otherwise from parent
- internal_note = some words
- preview_mode = bool
- debug_mode = bool
- cache_enabled = bool
- static_cache_enabled = bool
- cdn_enabled = bool
- cdn_settings = JSON
- frontend_settings = json (for feature flags, overrides etc.)
- created_at
- created_by
- created_by_type
- updated_at
- updated_by
- updated_by_type
- deleted_at
- deleted_by
- deleted_by_type

## Frontend_Domain_Localization_Theme

- frontend_id
- domain_id
- localization_id
- is_primary = canonical domain / language for this frontend
- redirect = id or bool?
- Theme?
- primary, redirect, www-also



## Slug

Replacements ...



## Blocks

- id
- type (paragraph, heading, table, ...)
- data (JSON)
- draft (timestamps, author fields)



## Block_Placements

- id
- type (page, post, template, product, ...)
- type_id
- slot (header, footer, ...)
- block_id -> blocks.id
- parent_placement_id (Nesting)
- position (int)
- timestamps



## Pages

- id
- title
- slug
- excerpt
- layout_id nullable → layouts.id
- content
- markdown
- data (JSON)
- draft (timestamps, author fields)



## Layouts

- id
- theme_id
- name
- layout (JSON - with slots)
- is_default bool
- draft (timestamps, author fields)



## Page Config

```php
'editors' => [
   'tiptap'  => true,
   'markdown' => true,
   'blocks'  => true,  // class_exists(\Moox\Blocks\MooxEditor::class)
],

'tiptap_storage' => 'html', // html or json
```

