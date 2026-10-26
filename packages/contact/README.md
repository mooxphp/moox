# Moox Contact

Contact records. One `contacts` table, an Eloquent model, and a Filament resource. Feature behavior comes from traits. Domain fields stay on the resource.

## Relations

`config/contact.php` wires `belongsTo` relations. `Contact::getResourceName()` returns `contact`, so Core reads that file.

```php
'relations' => [
    'language' => [
        'kind' => 'belongs_to',
        'presentation' => 'hidden',
        'model' => 'Moox\\Data\\Models\\StaticLanguage',
        'foreign_key' => 'language_id',
        'title_attribute' => 'common_name',
    ],
],
```

`kind` `belongs_to` builds the Eloquent relation. `presentation` `hidden` keeps it off a relation-manager tab. `title_attribute` is the select label. `organization_type_id` and `legal_form_id` are not in this config yet; the resource still shows them as numeric fields on the relation section.

## Model

| Trait | Package | What it does |
| --- | --- | --- |
| `Moox\Support\Identity\HasIdentifier` | moox/support | Sets `ulid` and `uuid`, then keeps them unchanged |
| `Moox\Support\Audit\HasAudit` | moox/support | Sets `created_by_*` and `updated_by_*`, then keeps `created_*` unchanged |
| `Moox\Core\Traits\Relations\HasRelations` | moox/core | Exposes each configured relation, for example `language()` and `country()` |
| `Illuminate\Database\Eloquent\SoftDeletes` | Laravel | Soft deletes |

## Resource

Form traits live in `moox/support`. The resource calls one section method per trait.

Left column:

| Trait | Section |
| --- | --- |
| `Moox\Support\Active\HasActiveForm` | Active |
| — | Fachfeld (`domainSection()` on the resource: `name_1`, `name_2`, `name_3`, `display_name`, `note`) |
| `Moox\Support\External\HasExternalForm` | External |
| `Moox\Support\Data\HasDataForm` | Data |

Right column, relation first:

| Trait | Section | When |
| --- | --- | --- |
| `Moox\Support\Relation\HasRelationForm` | Relation | Always. Selects come from `contact.relations` |
| `Moox\Support\Identity\HasIdentityForm` | Identity | Existing record |
| `Moox\Support\Audit\HasAuditForm` | Audit | Existing record |
| `Moox\Support\Archive\HasArchiveForm` | Archive | Existing record |
| `Moox\Support\SoftDelete\HasSoftDeleteForm` | SoftDelete | Existing record |

Labels and validation stay on the resource: `featureTranslation()` returns `contact::fields`, `featureRules()` returns `ContactRules`.
