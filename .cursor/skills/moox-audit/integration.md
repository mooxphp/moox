# Moox Audit — integration templates

## 1. Discover the target package

Read before editing:

| File | What to extract |
| --- | --- |
| `packages/{pkg}/src/Models/*.php` | Main model, translation model, `fillable` / `getCustomTranslatedAttributes`, `scope`, delete side-effects |
| `packages/{pkg}/config/{pkg}.php` | Existing config structure; add `audit` block before closing `];` |
| `packages/{pkg}/src/{Pkg}ServiceProvider.php` | `packageBooted()` for registry |
| `packages/{pkg}/src/Resources/*Resource.php` | Resource class for `filament` config + Activity tab wiring |

### Draft packages (BaseDraftModel)

Typical pair:

- **Main model** → preset `draft_main`, track non-translated columns (`is_active`, `status`, `scope`, `parent_id`, styling fields, …)
- **Translation model** → preset `draft_translation`, track `title`, `slug`, `description`, `content`, `translation_status`, `author_id`, `author_type`

Use the same `log_name` for both (e.g. `'tag'`).

**Attribute rule:** Only list columns that should appear in the audit diff. Omit internal morph `*_by_*` on translations (preset hides them). Include `scope` on the main model when present.

## 2. Config template

Add to `packages/{pkg}/config/{pkg}.php` (adjust imports at top):

```php
use Moox\{Pkg}\Models\{MainModel};
use Moox\{Pkg}\Models\{TranslationModel};
use Moox\{Pkg}\Resources\{MainResource};

// ...

    /*
    |--------------------------------------------------------------------------
    | Audit defaults
    |--------------------------------------------------------------------------
    |
    | Registered with moox/audit when installed. Override in config/audit.php.
    |
    */

    'audit' => [
        'enabled' => true,
        'models' => [
            {MainModel}::class => [
                'preset' => 'draft_main',
                'log_name' => '{package_key}',
                'attributes' => [
                    // main-model columns from fillable / business relevance
                ],
            ],
            {TranslationModel}::class => [
                'preset' => 'draft_translation',
                'log_name' => '{package_key}',
                'attributes' => [
                    'title',
                    'slug',
                    'description',
                    'content',
                    'translation_status',
                    'author_id',
                    'author_type',
                ],
            ],
        ],
        'hooks' => [
            // optional — see decisions.md
        ],
        'filament' => [
            {MainResource}::class => [
                'owner_model' => {MainModel}::class,
                'aggregate_subjects' => [
                    {TranslationModel}::class => 'translations',
                ],
            ],
        ],
    ],
```

## 3. ServiceProvider template

```php
use Moox\Audit\Support\AuditPackageRegistry;

public function packageBooted(): void
{
    // ... existing boot logic ...

if (
    class_exists(AuditPackageRegistry::class)
    && config('audit.enabled', true)
    && config('{package_key}.audit.enabled', true)
) {
    AuditPackageRegistry::register('{package_key}', config('{package_key}.audit', []));
}
}
```

## 4. Example: `moox/tag`

**Models:** `Tag` (main), `TagTranslation` (translation). `Tag` has `detachAllTaggables()` on `deleting` (like category + categorizables).

### `config/tag.php`

```php
use Moox\Tag\Models\Tag;
use Moox\Tag\Models\TagTranslation;
use Moox\Tag\Resources\TagResource;

// inside return [ ... ]:

    'audit' => [
        'enabled' => true,
        'models' => [
            Tag::class => [
                'preset' => 'draft_main',
                'log_name' => 'tag',
                'attributes' => [
                    'is_active',
                    'status',
                    'scope',
                    'color',
                    'weight',
                    'due_at',
                ],
            ],
            TagTranslation::class => [
                'preset' => 'draft_translation',
                'log_name' => 'tag',
                'attributes' => [
                    'title',
                    'slug',
                    'permalink',
                    'description',
                    'content',
                    'translation_status',
                    'author_id',
                    'author_type',
                ],
            ],
        ],
        'hooks' => [
            Tag::class => [
                'deleting' => [
                    'log_name' => 'tag',
                    'entry_type' => 'log',
                    'event' => 'taggables_detached',
                    'description' => 'taggables_detached',
                ],
            ],
        ],
        'filament' => [
            TagResource::class => [
                'owner_model' => Tag::class,
                'aggregate_subjects' => [
                    TagTranslation::class => 'translations',
                ],
            ],
        ],
    ],
```

No built-in `taggables_detached` handler exists — omit `handler` so `MooxActivityLogger::log()` runs (see `AuditHooks`). For category-style enriched properties, add a handler in `moox/audit` first, then reference it.

### `TagServiceProvider.php`

Same `AuditPackageRegistry::register('tag', config('tag.audit', []))` block as category.

### `TagResource.php`

```php
use Moox\Core\Traits\InteractsWithAuditResourceRelations;

class TagResource extends BaseDraftResource
{
    use InteractsWithAuditResourceRelations;
    // ...
}
```

Do not leave an empty `getRelations()` override — it blocks the trait.

## 5. Verification

1. `audit.enabled` is true (env `AUDIT_ENABLED` or default).
2. `AuditPlugin` registered in the Filament panel (app install).
3. Resource uses `InteractsWithAuditResourceRelations` (or merges the registry).
4. Edit a tracked record → **Activity** tab shows `audit` entries on attribute change.
5. Delete with pivot detach → `log` entry if hook configured.
6. Global **Audit** resource lists entries filtered by `log_name`.

## 6. App overrides (optional)

Consumers can tune without editing the package via published `config/audit.php`:

```php
'models' => [
    \Moox\Tag\Models\Tag::class => ['append_attributes' => ['count']],
],
'filament' => [
    \Moox\Tag\Resources\TagResource::class => ['enabled' => false], // hide Activity tab only
],
```

Merge rules: `attributes` **replaces**; `append_attributes` **appends**; `enabled => false` disables. See package README.
