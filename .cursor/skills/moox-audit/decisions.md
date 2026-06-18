# Moox Audit — integration decisions

## Preset selection

| Model type | Preset | Entry type | Events |
| --- | --- | --- | --- |
| Main draft entity (`BaseDraftModel`) | `draft_main` | `audit` | created, updated, deleted, restored |
| Translation row (`BaseDraftTranslationModel`) | `draft_translation` | `audit` | same + `locale` in properties |

Non-draft models: omit preset; set `events`, `entry_type`, `log_name`, `attributes` explicitly.

## Which attributes to track

1. Start from model `fillable` / `getCustomTranslatedAttributes()`.
2. Include business-meaningful fields only (status, scope, content, relations ids users care about).
3. Exclude high-churn or internal fields unless requested (`uuid`, `ulid`, `custom_properties` — case by case).
4. Main draft: always consider `scope` when the model uses `HasScopedModel`.
5. Translations: include `author_id` / `author_type` for enrichment; preset hides `*_by_*` morph columns.

## Entry types

| Type | When |
| --- | --- |
| `audit` | Default for model attribute changes (presets) |
| `log` | Hooks, manual `MooxActivityLogger::log()`, side-effect events |

## Hooks on delete

Check the main model's `booted()` / `deleting` listeners:

| Pattern | Example | Hook approach |
| --- | --- | --- |
| Detach pivot rows on delete | `Category::detach…`, `Tag::detachAllTaggables()` | `deleting` hook with `entry_type` => `log` |
| Built-in handler | Category categorizables | `'handler' => 'categorizables_detached'` |
| No built-in handler | Tag taggables | Omit `handler`; set `event` + `description` |

Built-in handlers in `Moox\Audit\Support\AuditHooks`:

- `categorizables_detached` — logs `category_id` in properties

For new pivot tables, prefer a generic hook first. Add a dedicated handler in `packages/audit` only when enriched properties are required.

### Generic hook (no handler)

```php
'deleting' => [
    'log_name' => 'tag',
    'entry_type' => 'log',
    'event' => 'taggables_detached',
    'description' => 'taggables_detached',
],
```

## Filament `aggregate_subjects`

Map translation model → relation name on the owner:

```php
'aggregate_subjects' => [
    TagTranslation::class => 'translations',
],
```

Use the Eloquent relation method name from the main model / draft base.

## Manual logging (outside config)

```php
use Moox\Audit\Services\MooxActivityLogger;

MooxActivityLogger::log('my-channel', 'Something happened', [
    'entry_type' => 'log',
    'event' => 'exported',
    'subject' => $model,
    'properties' => ['format' => 'csv'],
    'scope' => 'default',
]);
```

Use for one-off flows not covered by model config or hooks.

## Tests — when required

| Change | Test |
| --- | --- |
| New package audit config only | Optional smoke test if package has audit test harness |
| New hook handler in `moox/audit` | Unit test in `packages/audit/tests` |
| Consumer-specific hook behavior | Feature/unit test in consumer package |

## Out of scope for consumer integration

- Publishing `activity_log` migrations (app / `mooxaudit:install`)
- Registering `AuditPlugin` in `AdminPanelProvider` (app)
- Adding traits to models for CRUD tracking (not used in Moox audit)
