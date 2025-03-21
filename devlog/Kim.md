# Devlog Kim

-   [ ] [Localization Package](../packages/localization/docs/IDEA.md)
-   [ ] [Slug Package](../packages/slug/docs/IDEA.md)
-   [ ] [Data Package](../packages/data/docs/IDEA.md)
    -   $table->string('flag_code', 2); // Stores the exact 2-letter code for the flag to use
    -   $table->boolean('is_native_language')->default(false); // Language is in nativeName
    -   4 translation tables, locale nicht, felder mit inhalt dort löschen, nur noch relation
    -   model cleane ich dann, logik ist im importer
    -   write_protected feature, hier nur an/aus fertig (nicht feld oder datensatz ebene)
-   [ ] [Tag Package](../packages/tag/docs/README.md)
    -   Moox Slug
    -   Translatable
    -   Soft Delete
    -   Publishable
    -   Consumer API
    -   Factory
    -   Frontend
-   [ ] [Category Package](../packages/category/docs/README.md)
    -   Nested
    -   Moox Slug
    -   Translatable
    -   Soft Delete
    -   Publishable
    -   Consumer API
    -   Factory
    -   Frontend
-   [ ] [Item Package](../packages/item/docs/README.md)
    -   Author
    -   Factory
    -   Consumer API
-   [ ] [Record Package](../packages/record/docs/README.md)
    -   Translatable
    -   Soft Delete
    -   Factory
    -   Consumer API
-   [ ] [Draft Package](../packages/draft/docs/README.md)
    -   Moox Slug
    -   Translatable
    -   Soft Delete
    -   Publish / Unpublish
    -   Frontend
    -   Factory
    -   Consumer API

## Common API for Entities

### Fields

-   Item
    -   Created at
    -   Created by id
    -   Created by type
    -   Updated at
    -   Updated by id
    -   Updated by type
    -   write_protected
-   Record adds
    -   To delete at
    -   Deleted at
    -   Deleted by id
    -   Deleted by type
    -   Restored at
    -   Restored by id
    -   Restored by type
    -   Localization
-   Draft adds
    -   Title
    -   Slug
    -   To publish at
    -   Published at
    -   Published by id
    -   Published by type
    -   To unpublish at
    -   Unpublished at
    -   Unpublished by id
    -   Unpublished by type

### Config (per Entity)

```php
return [
'navigation_group' => '',
'single' => 'trans//',
'plural' => 'trans//',
'tabs' => [],
'backend_readable_fields' => [],
'backend_writable_fields' => [],
'frontend_readable_fields' => [],
'frontend_writable_fields' => [],
'api_readable_fields' => [],
'api_writable_fields' => [],
'taxonomies' => [/* only items */],
'relations' => [/* only items */],
'modules' => [/* items and taxonomies */],
'authors' => [/* items and taxonomies */],
'auditable' => false,
],
```
