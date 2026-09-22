# Moox Mail Testing

Benchmark MJML to HTML for personalized mails: **PHP vs Node**, same template, same payloads. The package does not send mail. It composes MJML from `moox/mail-template`, converts it with `moox/mjml`, stores the HTML, and times each step.

The package is part of the **Moox ecosystem** — Filament packages for Laravel apps. Learn more about [Moox](https://moox.org).

## Features

- Filament page **MJML run** (`/admin/mail-testing`): configure a run, set the test template, start it, watch progress
- Filament resource **Test mails**: list, filter, preview, compare PHP and Node HTML, delete
- Two engines per run: PHP (`shyim/mjml-php` via `moox/mjml`) or Node (`spatie/mjml-php`)
- Persist HTML on the configured disk or in the database
- Token list with **Demo** or **Random** values; recipient Demo is Max Mustermann
- PHP vs Node comparison: normalized SHA-1 hashes, byte and character counts, side-by-side HTML sample
- Artisan command `mail-testing:render` for the same options as the UI
- Dedicated queue `mail-testing` (configurable), or inline when no worker is listening

## Requirements

| Package | Role |
| --- | --- |
| `moox/mjml` | MJML to HTML (PHP and Node engines) |
| `moox/mail-template` | Layouts, templates, `{token}` interpolation |
| `moox/contact` | Recipient payload (name, salutation) |
| `moox/core` | Moox service provider / installer |

A Filament panel named `admin` is expected. The plugin registers itself on that panel.

Node runs need a working Node MJML setup in `moox/mjml` (`MJML_USE_PHP_RENDERER=false` is set only for the duration of a Node run). See [Moox MJML](https://moox.org/docs/mjml) for Node, `mjml` npm, and `MJML_NODE_PATH`.

## Installation

```bash
composer require moox/mail-testing
php artisan migrate
```

Or use the Moox installer:

```bash
composer require moox/mail-testing
php artisan moox:install
```

Publish the config if you want a local copy:

```bash
php artisan vendor:publish --tag=mail-testing-config
```

Migrations (`mail_testing_runs`, `mail_testing_messages`) ship with the package. `runsMigrations()` is enabled, so they also run with the package installer.

### Plugin

On the default `admin` panel the package registers `MailTestingPlugin` itself. For another panel, add:

```php
use Moox\MailTesting\Plugins\MailTestingPlugin;

MailTestingPlugin::make(),
```

That adds:

- Page **MJML run** (navigation group **Mail Testing**)
- Resource **Test mails**
- Authenticated preview route `mail-testing-messages.preview`

## Configuration

`config/mail-testing.php`:

| Key | Env | Default | Meaning |
| --- | --- | --- | --- |
| `default_count` | `MAIL_TESTING_DEFAULT_COUNT` | `500` | Default number of mails on the form |
| `min_count` | — | `1` | Minimum count |
| `template_slug` | — | `test` | Mail template used for every run |
| `layout_slug` | — | `mail-testing` | No longer used. The run uses the test template layout, or the layout selected on the page |
| `disk` | `MAIL_TESTING_DISK` | `local` | Disk for HTML files and the saved variable list |
| `timeout` | `MAIL_TESTING_JOB_TIMEOUT` | `0` | Queue job timeout in seconds (`0` = no limit) |
| `progress_every` | — | `25` | How often the run row is updated during a run |
| `queues.connection` | `MAIL_TESTING_QUEUE_CONNECTION` | `null` | Queue connection; empty uses Laravel’s default |
| `queues.name` | `MAIL_TESTING_QUEUE` | `mail-testing` | Queue name |

On disk `local`, HTML lives under `storage/app/private/mail-testing/{runId}/{position}.html`.

## First run (Filament)

1. Open **Mail Testing → MJML run**.
2. **Set template** stores the MJML body on the test template. Leave **source layout** empty to keep the layout already on that template. Choose a layout to attach its logo, colors, and footer to the template. Default tokens: `{anrede}`, `{displayName}`, `{firstName}`, `{lastName}`.
3. Set **count**, **engine** (PHP or Node), and **storage**. An empty source layout uses the template layout. A selected layout is used for that run only.
4. Optionally add tokens under **Variables** and click **Save**.
5. Start the run.

If `QUEUE_CONNECTION` is not `sync` and a worker is listening on `mail-testing`, the run is queued. Otherwise it runs in the HTTP request (`set_time_limit(0)`).

Worker:

```bash
php artisan queue:work --queue=mail-testing --tries=1 --timeout=0
```

`queue:work` keeps classes in memory. After changing converter, composer, or payload code, restart the worker (`php artisan queue:restart` or stop/start the process).

## Run options

These options are stored on the run as JSON and included in `options_fingerprint`. PHP vs Node comparison only makes sense when two completed runs share the same fingerprint and count.

### Test

| Option | Values | Notes |
| --- | --- | --- |
| Source layout | Existing `mail_layouts.id` | Used only when creating/updating the test template, not during convert |
| Count | Integer ≥ `min_count` | Number of HTML mails in the run |
| Engine | `php`, `node` | Sets `mjml.use_php_renderer` for this run, then restores the previous value |
| Storage | `storage`, `database` | `storage`: one file per mail. `database`: HTML in `mail_testing_messages.html` |

### Variables

| Option | Values | Notes |
| --- | --- | --- |
| Recipient | `demo`, `random` | Demo is always Max Mustermann (`salutation_code` `mr`). Random uses `Contact::factory()->make()` |
| Tokens | Repeater: token, mode, value | Token name without `{}`. Last row wins if a token is duplicated |

Recipient and token values are seeded with `crc32(fingerprint + '|' + position)` so PHP and Node produce the same random data for the same position.

| Token mode | Value field | Result |
| --- | --- | --- |
| Demo | Fixed text | Used as-is (empty string is allowed) |
| Random | Pattern such as `RE-####` | `fake()->bothify($value)`; empty pattern → `fake()->word()` |
| Random + token `invoiceNumber` | Ignored | `RE-2026-{position}` zero-padded to five digits, e.g. position 1 → `RE-2026-00001` |

Bindings override contact keys (`anrede`, `displayName`, `firstName`, `lastName`) when the token matches.

The list is also written to `{disk}/mail-testing-variables.json` on **Save** and when a run starts. That file is **outside** `mail-testing/`, so **Delete test data** does not remove it.

Contact fields on the payload:

| Token | Source |
| --- | --- |
| `{anrede}` | Formal German salutation from `salutation_code`, academic title, last name |
| `{displayName}` | `display_name`, or first + last name |
| `{firstName}` | `first_name` |
| `{lastName}` | `last_name` |

### MJML (collapsed on the form; defaults are enough)

Passed through to `Moox\Mjml\Mjml`:

| Option | Default | Meaning |
| --- | --- | --- |
| Validation | `soft` | `skip`, `soft`, or `strict` (`Moox\Mjml\Enums\ValidationLevel`) |
| Minify | off | Minify HTML |
| Beautify | off | Pretty-print HTML |
| Keep comments | off | Keep HTML comments |
| Ignore includes | off | Do not resolve MJML includes |

PHP validation is stricter than Node. Mixed body MJML (`mj-text` then `mj-section`) must be valid for the document composer in `moox/mail-template` (and any app composer bound to `MjmlDocumentComposer`).

## Artisan

```bash
php artisan mail-testing:render --count=50 --engine=php --persist=storage --validation=soft
php artisan mail-testing:render --count=50 --engine=node --persist=storage --validation=soft
```

| Flag | Default | Same as UI |
| --- | --- | --- |
| `--count=` | `mail-testing.default_count` | Count |
| `--engine=` | `php` | `php` or `node` |
| `--persist=` | `storage` | `storage` or `database` |
| `--validation=` | `soft` | `skip`, `soft`, `strict` |
| `--minify` | off | Minify |
| `--beautify` | off | Beautify |
| `--keep-comments` | off | Keep comments |
| `--ignore-includes` | off | Ignore includes |

The command runs **inline** (no queue). Recipient mode and the token list are not CLI flags; they come from `mail-testing-variables.json` only if you start from Filament. The Artisan command currently stores only the MJML flags on `options` (no `recipient_mode` / `variables`), so factory random contacts are used unless you extend the command.

The Filament **Environment** block shows the equivalent `mail-testing:render` line for the current form (without variables).

## Queue

`RenderMailTestingRunJob`:

- Queue: `config('mail-testing.queues.name')` (default `mail-testing`)
- Connection: `config('mail-testing.queues.connection')` when set
- `$tries = 1`
- `$timeout` from `mail-testing.timeout` (default `0`)

A heartbeat is written on `Illuminate\Queue\Events\Looping` when the worker listens to that queue (15 seconds). The page uses it to decide queued vs inline start. Polling is 1s while a run is pending/running, otherwise 2s if queues are used.

## Results

The **Runs** table shows the latest run per engine:

| Column | Meaning |
| --- | --- |
| Compose | Build MJML from template + payload |
| Convert | MJML → HTML (the engine under test) |
| Persist | Write HTML to disk or database |
| Generation | Compose + convert |
| Total | Whole run including persist and bookkeeping |

Times are shown in milliseconds, seconds, or minutes.

Failed runs keep `error` (for example MJML validation). Delete one engine run from the table; the other engine stays.

### PHP vs Node

Shown when both engines have a completed run.

- Pairs are matched by **position**.
- Identity uses SHA-1 of HTML with whitespace collapsed (`NormalizedHtml::hash`).
- The sample is the first mismatch (or position 1), shown with original line breaks, wrap, and scroll.
- Headers show **bytes** and **Unicode characters** of the original HTML. Differing sizes use the warning color.
- A warning badge appears when fingerprints or counts differ.
- If every pair has the same delta (one PHP hash, one Node hash, zero identical), a note says one sample is enough.

## Test mails

**Mail Testing → Test mails**:

- Columns: run, engine, position, hash (truncated, copyable), size, compose / convert / persist
- Filters: engine, run
- **Preview** opens the stored HTML in a new tab (authenticated). `app.url` in the HTML is rewritten to the current request host so assets load.
- **View** shows metadata, timings, character count, and a live iframe. If the peer engine has the same position, PHP and Node sit side by side with byte/character counts.
- **Delete test data** removes all runs, messages, and the `mail-testing/` directory on the disk. It does not delete `mail-testing-variables.json`.

## Storage layout

```
{disk}/
  mail-testing-variables.json          # saved recipient + tokens
  mail-testing/
    {runId}/
      1.html
      2.html
      …
```

`MailTestingRun::purge()` deletes one run’s files and rows. `MailTestingRun::purgeAll()` deletes the whole `mail-testing/` tree and all rows.

## How a mail is built

For each position `1…count`:

1. `PayloadResolver` builds the token map (contact + bindings).
2. `MailTestingConverter::compose()` → `MailTemplateRenderer::toMjml()`.
3. `MailTestingConverter::convert()` → `Mjml::new()` with the run’s MJML flags → HTML.
4. Persist + `html_hash` + `byte_length` + per-mail timings.

The template slug is always `mail-testing.template_slug` (`test`). Create it from the page before the first run.

## License

The MIT License (MIT). See [license and copyright](https://github.com/mooxphp/moox/blob/main/LICENSE.md).
