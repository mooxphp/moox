# Moox Transform

Structured data transformation into Eloquent models with validation, audit records, and optional bulk/expand execution.

Two concepts:

- **`TransformDefinition`** — reusable configuration (what to transform)
- **`TransformRecord`** — one concrete run (what happened)

## Data model

### `transform_definitions`

| Field | Notes |
|-------|-------|
| `name` | Unique |
| `destination_model` | Eloquent class |
| `destination_match` | Required for active definitions — upsert keys |
| `source_references` | JSON array of sources |
| `field_map` | Destination field → source path |
| `validation_rules` | Optional extra rules |
| `execution_mode` | `single` (default), `expand`, `bulk` |
| `expand` | JSON — dedupe, locales, nested expansion |
| `bulk` | JSON — chunk size, persist children, write strategy |
| `is_active` | bool |

### `transform_records`

One run: `source_projection`, optional runtime `source_references`, `input_hash`, status, validation errors, warnings, `bulk_stats`, timestamps.

## Source types (`source_references`)

| `source_type` | Single row | Iterable (bulk/expand) | Filament UI |
|---------------|----------|----------------------|-------------|
| `db_table` | yes (`row_key`) | yes (no `row_key` + optional `where`) | yes |
| `file_json` | yes | no | yes |
| `file_csv` | yes (`row_key` required) | no | yes |
| `api` | yes | no | yes |
| `api_import_record` | yes | yes (list payload) | yes |
| `static` | yes (`data` object) | no | yes |

### Common reference fields

- **All types:** `alias`, `selector` (where applicable)
- **`db_table`:** `connection`, `table`, `key_column`, `columns`, `row_key`, `row_key_from`, `where`
- **`api`:** `url`, `query`
- **`api_import_record`:** `record_id`, `item_key`, `selector`
- **`static`:** `data` (JSON object)
- **`file_*`:** `path`, `key_column`, `row_key`

## Execution modes

| Mode | Behavior |
|------|----------|
| `single` | One source payload → one destination write |
| `expand` | One payload expanded (dedupe, nested lists, locales) → child records |
| `bulk` | Iterable source (`db_table` without `row_key`, or `api_import_record` list) → many writes; optional chunking |

### `expand` options

- `dedupe_by` — dot path for deduplication key
- `prefer` — rules to pick a winner when deduping
- `locales` — expand translation rows from a list (`source`, `language_key`, `alias`, `only`)
- `nested` — expand nested lists (`path`, `alias`, `dedupe_by`)

### `bulk` options

- `chunk_size` — batch size (default from config)
- `persist_children` — store child transform records
- `write_strategy` — `row` or `batch`

## Inline field expressions

Registered via `config('transform.inline_value_operations')`:

- `map`, `case`, `truthy`, `not_truthy`, `int`
- `coalesce`, `any_truthy`
- `lookup_id:ModelClass,column,source.path`

Package default does **not** include `status_from_deleted` — register it in the app if needed.

## Filament UI

`TransformDefinitionResource`:

- Source type select with conditional fields
- Field map with destination/source path datalists
- Execution mode, expand, bulk sections
- Run action (queues `RunTransformRecordJob`)

`TransformRecordResource`:

- View run status, errors, `source_projection` (KeyValue)

### Model discovery

Filament scans `app/Models` plus `config('transform.additional_model_scan_paths')`. Add package model directories in the app config.

## App bindings (required for some features)

| Config key | Purpose |
|------------|---------|
| `import_record_payload_reader` | Class implementing `ImportRecordPayloadReader` — required for `api_import_record` |
| `import_record_model` | Model for Filament run dialog |
| `locale_variant_resolver` | Class implementing `LocaleVariantResolver` — used by locale expansion |
| `default_source_projection` | Runtime context defaults (e.g. `context.defaults.status`) |
| `additional_model_scan_paths` | Extra model directories for Filament |
| `inline_value_operations` | App-specific operations (e.g. `StatusFromDeletedInlineValueOperation`) |

Bindings are **null in the package** — the app must configure them.

## Runtime flow

1. Load active `TransformDefinition`
2. Resolve sources (`source_projection` + `source_references`)
3. Expand/bulk if configured
4. Map fields via `field_map` + inline operations
5. Validate (model rules + definition rules)
6. Upsert destination model (match via `destination_match`)
7. Persist `TransformRecord` status, warnings, errors

Runs via `TransformRunner` (sync) or `RunTransformRecordJob` (queued).

## What this package does not provide

- **No API fetching** — `api` source does a simple HTTP GET only; no auth, pagination, or retry logic (use `moox/connect` for that)
- **No import record storage** — `api_import_record` reads via app-bound reader (typically Connect's `ApiImportRecord`)
- **No ERP/customer-specific field maps** — definitions live in the app (migrations, seeds, Filament)
- **No locale normalization rules** — app provides `LocaleVariantResolver` (e.g. `cz` → `cs`)
- **No automatic scheduling** — trigger runs yourself or from Connect/transform jobs in the app
- **No Filament panel by default** — `enable-panel` is `false`; register `TransformPlugin` in the app panel

## Relation to `moox/connect`

Connect fetches API data and stores it in `api_import_records`. Transform reads those records via `api_import_record` sources and writes to destination models. They are separate packages; wiring is app config.

## Installation

```bash
composer require moox/transform
php artisan vendor:publish --tag=transform-config
php artisan migrate
```

Register `Moox\Transform\Filament\Plugins\TransformPlugin` in your Filament panel.

## Minimal example

```php
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;
use Moox\Transform\Jobs\RunTransformRecordJob;

$definition = TransformDefinition::query()->create([
    'name' => 'products-sync',
    'destination_model' => \Moox\Product\Models\Product::class,
    'destination_match' => ['sku' => 'legacy.sku'],
    'source_references' => [[
        'source_type' => 'db_table',
        'connection' => 'mysql',
        'table' => 'legacy_products',
        'key_column' => 'sku',
        'row_key' => 'P-15',
        'alias' => 'legacy',
    ]],
    'field_map' => [
        'sku' => 'legacy.sku',
        'name' => 'legacy.title',
        'price' => 'legacy.price',
    ],
    'validation_rules' => [
        'sku' => ['required'],
        'name' => ['required', 'string'],
    ],
    'is_active' => true,
]);

$record = TransformRecord::query()->create([
    'transform_definition_id' => $definition->id,
    'source_references' => $definition->source_references,
]);

RunTransformRecordJob::dispatch($record->id);
```

## Tests

```bash
php artisan test --compact packages/transform/tests
```

## License

MIT — see [LICENSE.md](LICENSE.md).
