# Moox Demo

Seed demo data for **installed** Moox packages: static reference data, localizations, package seeders (dependency-aware order), factory-generated entities, and optional demo media files.

Requires **`moox/core`**. Package discovery for `moox:demo` lives in this package (`Moox\Demo\Support\MooxPackageDiscovery`). Other Moox packages are used at runtime when installed (`moox/data`, `moox/localization`, `moox/media`, `moox/draft`, etc.).

For queue job examples, see **[Moox Jobs](../jobs/README.md)**.

## Requirements

- PHP 8.2+
- Laravel 12+ (Moox dev app)
- `moox/core`
- Migrations for packages you want to seed (`php artisan moox:install` recommended)

### Optional packages (features)

| Package | Used for |
|---------|----------|
| `moox/data` | Countries, languages, currencies (`DataSeeder`) |
| `moox/localization` | Localization records |
| `moox/media` | Mediathek + demo file import |
| `moox/category`, `moox/draft`, `moox/product`, … | Package seeders + factory entities |

## Installation

```bash
composer require moox/demo
```

In the Moox monorepo dev app, enable `demo` in `config/devlink.php` and run `composer update`.

No Filament entity is registered by this package. You do **not** need a separate `moox:install` step for `moox/demo` itself.

## Prerequisites

1. Install Moox packages you need (`php artisan moox:install`).
2. For category seeding, a user must exist — `moox:demo` can create a demo user (see [Configuration](#configuration)).

## Command: `php artisan moox:demo`

Seeds the application using installed Moox packages. Packages that are not installed are skipped.

### Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `--languages` | integer | `3` | Number of localizations when `--locales` is omitted |
| `--locales` | string | — | Comma-separated locale variants (e.g. `de_DE,en_GB,fr_FR`). Overrides `--languages` |
| `--dataset` | string | `small` | Records per entity for factory seeding: `small`, `medium`, `large`, `huge` |
| `--fresh` | flag | `false` | Runs `migrate:fresh` before seeding (**destroys all data**) |
| `--skip-seeders` | flag | `false` | Skips package entry seeders |
| `--skip-factories` | flag | `false` | Skips factory-based entity seeding |
| `--skip-media` | flag | `false` | Skips copying files from `resources/demo/media/` |
| `-v`, `-vv` | flag | — | Verbose output (seeder order, skipped steps) |

### Dataset sizes

| `--dataset` | Records per factory entity |
|-------------|----------------------------|
| `small` | 100 |
| `medium` | 1,000 |
| `large` | 10,000 |
| `huge` | 100,000 |

Configured in `config/demo.php` under `dataset_sizes`.

### What the command does (pipeline)

1. **`--fresh`** — optional `migrate:fresh --force` (with confirmation in interactive mode).
2. **`moox/data`** — runs `DataSeeder` only (static countries, languages, currencies, …).
3. **Localizations** — creates/updates rows for `--locales` or default locales (`de_DE`, `en_US`, `es_ES`).
4. **Demo media** — copies `resources/demo/media/*` to the configured storage disk.
5. **Demo user** — creates `demo@moox.org` if no user exists (when enabled in config).
6. **Other package seeders** — runs one **entry seeder** per installed Moox package in **dependency order** (topological sort + `config/demo.php` priorities). Skips nested seeders already called by `DataSeeder`.
7. **Factory seeding** — for packages with `extra.moox.install.auto_entities` and a model factory, creates `--dataset` records (with locales when factories support `withLocales()` / `withTranslationLocales()`).

### Examples

Minimal demo (3 locales, 100 records per factory entity):

```bash
php artisan moox:demo
```

Custom locales and medium dataset:

```bash
php artisan moox:demo --locales=de_DE,en_GB,fr_FR,ar_IR,es_ES --dataset=medium
```

Reset database and seed:

```bash
php artisan moox:demo --fresh --dataset=small
```

Only seeders and localizations (no factories, no media files):

```bash
php artisan moox:demo --skip-factories --skip-media
```

Large stress test (can take a long time and use significant memory):

```bash
php artisan moox:demo --dataset=huge
```

## Seeder documentation

**German, in-depth guide** (registration, pipeline, `UserSeeder` + `CategorySeeder` as reference implementations, Demo API, troubleshooting):

→ **[docs/SEEDERS.md](docs/SEEDERS.md)**

## Seeder order and dependencies

Seeders are **not** run in alphabetical file order. `moox:demo` uses:

- **Topological sort** of `moox/*` composer dependencies
- **One entry seeder per package** (`extra.moox.install.seed`, e.g. `DataSeeder`, not `StaticLanguageSeeder` alone)
- **Manual priority** via `seeder_order` in `config/demo.php`

Typical order:

```text
moox/data (DataSeeder)
  → localizations (CLI step or LocalizationSeeder)
  → demo media / moox/media
  → demo user
  → moox/attribute, moox/tag, moox/category, moox/draft, …
  → factory loops (product, draft, …)
```

`CategorySeeder` expects users, localizations, and media — run `moox:demo` after `moox/data` and prefer having `moox/media` installed.

## Demo media

### Static asset packs (offline)

Bundled under `resources/demo/assets/`:

```text
assets/images/products/   # product / category photos
assets/images/users/      # user avatar photos
assets/files/pdf/         # PDF samples
assets/files/documents/   # txt, docx, xlsx
assets/files/audio/       # mp3 sample
assets/videos/short/      # mp4 / webm clips
```

Sources and licenses: [`resources/demo/assets/MEDIA_SOURCES.md`](resources/demo/assets/MEDIA_SOURCES.md).

### Storage copy (root media folder)

Files placed directly in `resources/demo/media/` (not subfolders) are copied to `storage` on the disk defined in `config/demo.php` (`media.disk`, `media.directory`). When `moox/media` is installed, attach media to entities via category/draft seeders or the Mediathek UI.

## Configuration

Publish config:

```bash
php artisan vendor:publish --tag=demo-config
```

Key settings in `config/demo.php`:

- `dataset_sizes` — map dataset name → record count
- `default_locales` / `default_language_count`
- `seeder_order` — slug priority list
- `seeder_skip` — packages never seeded by demo (e.g. `demo`, `core`)
- `nested_seeder_basenames` — seeders only invoked by a parent seeder
- `demo_user` — auto-create demo user for category seeding

Factories can read `config('demo.locales')` and `config('demo.dataset_count')` during the factory step.

## Troubleshooting

| Issue | Action |
|-------|--------|
| No languages in `static_languages` | Install `moox/data`, run `moox:demo` (or `DataSeeder` first) |
| Category seeder fails / no user | Enable `demo_user` in config or run `php artisan make:filament-user` |
| `huge` runs out of memory or time | Use `medium` or `small`, or `--skip-factories` |
| Seeder class not found | Ensure `extra.moox.install.seed` points to a class under `Moox\{Package}\Database\Seeders` |
| Nothing seeded for a package | Package may not be installed or listed in `seeder_skip` |

## Related commands

| Command | Description |
|---------|-------------|
| `php artisan moox:install` | Install Moox packages (migrations, configs, plugins) |

## License

MIT. See [LICENSE.md](LICENSE.md) when present.
