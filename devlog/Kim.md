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
    -   Frontend
-   [ ] [Category Package](../packages/category/docs/README.md)
    -   Nested
    -   Moox Slug
    -   Translatable
    -   Soft Delete
    -   Publishable
    -   Consumer API
    -   Frontend
-   [ ] [Item Package](../packages/item/docs/README.md)
    -   Author
    -   Consumer API
-   [ ] [Record Package](../packages/record/docs/README.md)
    -   Moox Slug
    -   Translatable
    -   Soft Delete
    -   Consumer API
-   [ ] [Draft Package](../packages/draft/docs/README.md)
    -   Moox Slug
    -   Translatable
    -   Soft Delete
    -   Publish / Unpublish
    -   Frontend
    -   Consumer API

## Common API for Entities

### Fields

-   Title (publishable resource)
-   Slug (publishable resource)
-   Created at
-   Created by id
-   Created by type
-   Updated at
-   Updated by id
-   Updated by type
-   To delete at (soft delete only)
-   Deleted at (soft delete only)
-   Deleted by id (soft delete only)
-   Deleted by type (soft delete only)
-   To publish at (publishable resource)
-   Published at (publishable resource)
-   Published by id (publishable resource)
-   Published by type (publishable resource)
-   To unpublish at (publishable resource)
-   Unpublished at (publishable resource)
-   Unpublished by id (publishable resource)
-   Unpublished by type (publishable resource)
-   write_protected

### Config (Entity)

-   write_protected (bool)
-   write_protected_fields (array)

-   read_api (bool)
-   write_api (bool)
-   read_api_fields (array)
-   write_api_fields (array)

-   read_frontend (bool)
-   write_frontend (bool)
-   read_frontend_fields (array)
-   write_frontend_fields (array)

-   auditable (bool)
-   taxonomies (array, only items)
-   relations (array, only items)
-   modules (array, items and taxonomies)
-   authors (array, items and taxonomies)
