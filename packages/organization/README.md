# Moox Organization

Organization types and legal forms. Each entity is one table, an Eloquent model, and a Filament resource. Feature behavior comes from traits.

Organization types are translatable. Legal forms are not. `title` and `slug` sit on `legal_forms`. There is no `permalink_id`.

## Organization type

`organization_types` plus `organization_type_translations`. `OrganizationType::getResourceName()` returns `organization`. `config/organization.php` has an empty `relations` list.

Translated attributes are `title` and `slug`.

### Model

| Trait | Package | What it does |
| --- | --- | --- |
| `Moox\Support\Identity\HasIdentifier` | moox/support | Sets `ulid` and `uuid`, then keeps them unchanged |
| `Moox\Support\Audit\HasAudit` | moox/support | Sets `created_by_*` and `updated_by_*`, then keeps `created_*` unchanged |
| `Astrotomic\Translatable\Translatable` | astrotomic/laravel-translatable | Stores `title` and `slug` per locale |
| `Illuminate\Database\Eloquent\SoftDeletes` | Laravel | Soft deletes |

### Resource

`OrganizationTypeResource` calls one section method per trait.

Left column:

| Trait | Section |
| --- | --- |
| `Moox\Support\Active\HasActiveForm` | Active |
| `Moox\Support\Title\HasTitleForm` | Title, as a translation repeater |

Right column:

| Trait | Section | When |
| --- | --- | --- |
| `Moox\Support\Identity\HasIdentityForm` | Identity | Existing record |
| `Moox\Support\Audit\HasAuditForm` | Audit | Existing record |
| `Moox\Support\SoftDelete\HasSoftDeleteForm` | SoftDelete | Existing record |

`featureTranslation()` returns `organization::fields`.

## Legal form

`legal_forms`. `LegalForm::getResourceName()` returns `legal-form`, so Core reads `config/legal-form.php`.

`title` examples named in the structure draft: GmbH, OHG, Inc., S.A.R.L, sp. zoo, e. V. The package does not seed them.

### Relations

```php
'relations' => [
    'country' => [
        'kind' => 'belongs_to',
        'presentation' => 'hidden',
        'model' => 'Moox\\Data\\Models\\StaticCountry',
        'foreign_key' => 'country_id',
        'title_attribute' => 'common_name',
    ],
],
```

`kind` `belongs_to` builds `country()`. `presentation` `hidden` keeps it off a relation-manager tab. `country_id` is nullable and has no foreign key. The select label is `StaticCountry.common_name`.

### Model

| Trait | Package | What it does |
| --- | --- | --- |
| `Moox\Support\Identity\HasIdentifier` | moox/support | Sets `ulid` and `uuid`, then keeps them unchanged |
| `Moox\Support\Audit\HasAudit` | moox/support | Sets `created_by_*` and `updated_by_*`, then keeps `created_*` unchanged |
| `Moox\Core\Traits\Relations\HasRelations` | moox/core | Exposes the configured `country()` relation |
| `Illuminate\Database\Eloquent\SoftDeletes` | Laravel | Soft deletes |

### Resource

`LegalFormResource` calls one section method per trait. Title uses `HasTitleSlugForm` directly, because the fields are columns on `legal_forms` and not translations.

Left column:

| Trait | Section |
| --- | --- |
| `Moox\Support\Active\HasActiveForm` | Active |
| `Moox\Support\Title\HasTitleSlugForm` | Title (`title`, `slug`) |

Right column, relation first:

| Trait | Section | When |
| --- | --- | --- |
| `Moox\Support\Relation\HasRelationForm` | Relation | Always. The country select comes from `legal-form.relations` |
| `Moox\Support\Identity\HasIdentityForm` | Identity | Existing record |
| `Moox\Support\Audit\HasAuditForm` | Audit | Existing record |
| `Moox\Support\SoftDelete\HasSoftDeleteForm` | SoftDelete | Existing record |

`featureTranslation()` returns `organization::fields`. `featureRules()` requires `is_active` to be boolean.
