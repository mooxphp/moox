# Moox Block Editor — decisions

## 1. Block type name

| Choice | Value |
| --- | --- |
| JSON `type` | `dynamicFeed` (camelCase) |
| Editor label | „Dynamischer Inhalt“ (no emoji in label) |

## 2. Locale resolution (Variant B — binding)

Priority in `BlockEditorLocale::resolveActive()`:

1. `request()->query('lang')`
2. `request()->input('lang')`
3. `app()->getLocale()`
4. `config('app.locale')`

Locale flows explicitly: `Controller` → `RenderContext` / `EntityQueryDefinition` → `EntityQueryBuilder` / sources.

**v1 rules:**

- No `request()` inside entity sources or mappers
- No silent fallback to other locales when translation is missing

## 3. Consumer registry pattern

Same as `moox/audit`:

- Config block in consumer package (`dynamic_feed`)
- `EntityQuerySourceRegistry::register()` in `packageBooted()`
- Guard: `class_exists(EntityQuerySourceRegistry::class)`
- `composer suggest` for `moox/block-editor`, not hard `require` in optional entity packages

`feed_item_mapper` in config as **string class name** to avoid autoload on config load.

## 4. Query generation

- No hand-written query classes per entity in v1
- `ConfigDraftEntityQuerySource` + `EntityQueryBuilder` apply filters via config `apply` conventions (`taxonomy:category`, `column:*`)
- `FeedItemMapper` only maps model → array

## 5. Public rendering vs editor config

| Context | Mechanism |
| --- | --- |
| Public pages | `BlockContentRenderer` / `<x-moox-editor::block-content>` — no per-block HTTP |
| Editor field | Embedded `data-dynamic-feed-sources` from `DynamicFeedEditorCatalog` |

Invalid `sourceKey` / `view`: log warning, render empty (no fatal).

## 6. Limits & security

- Server-side limit clamp: **1–50** (`moox-editor.dynamic_feed.max_limit`)
- Blade views: escape output (`{{ }}`); teasers via `*_plain` + `strip_tags` in mapper
- Editor catalog: locale at field render time; only published draft translations in public queries

## 7. Optional dependencies

| Package | Needs block-editor? |
| --- | --- |
| `moox/page` | Yes (content field + rendering) |
| `moox/news` | Optional (dynamic feed only) |
| `moox/frontend` | Suggest only (enhanced rendering in `moox::page.default`) |

## 8. When to add a custom EntityQuerySource class

Stay on config-driven source unless you need:

- Non-draft entity shape without translations
- Custom authorization beyond published draft defaults
- Query logic that cannot be expressed via `filter_schema` + `sortable_columns`

If adding a class, implement `EntityQuerySource` in the **consumer** package, not in `block-editor`.
