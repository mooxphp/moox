# Moox MJML

Converts MJML markup to HTML through a stable Moox API. Packages that send mail compose MJML themselves; this package only renders.

Two engines are required and selected by config. There is no silent fallback: a failure of the selected engine is raised as an exception.

Callers should depend on `Moox\Mjml\*` only: `Mjml`, `MjmlResult`, `MjmlError`, `Enums\ValidationLevel`, and `Exceptions\CouldNotRenderMjml`. Do not import Spatie or shyim types.

## Installation

```bash
composer require moox/mjml
```

Publish the config if you want a local copy:

```bash
php artisan vendor:publish --tag=mjml-config
```

## API

```php
use Moox\Mjml\Enums\ValidationLevel;
use Moox\Mjml\Mjml;

$html = Mjml::new()->toHtml($mjml);

$result = Mjml::new()->convert($mjml);
$result->html();
$result->hasErrors();
$result->errors();

Mjml::new()->canConvert($mjml);
Mjml::new()->canConvertWithoutErrors($mjml);

$minified = Mjml::new()->minify()->toHtml($mjml);
$checked = Mjml::new()->validationLevel(ValidationLevel::Soft)->convert($mjml);
```

The fluent methods follow the Spatie mjml-php surface: `keepComments()`, `hideComments()`, `ignoreIncludes()`, `beautify()`, `minify()`, `validationLevel()`, `filePath()`, `workingDirectory()`, and `sidecar()`. `toHtml()` and `convert()` also accept an options array as the second argument; array values override fluent ones.

Options that were not set keep each engine's own defaults, so `toHtml($mjml)` without options stays as it is today.

## Engine choice

`mjml.use_php_renderer` (env `MJML_USE_PHP_RENDERER`, default `true`):

| Value | Engine | Runtime |
| --- | --- | --- |
| `true` | [shyim/mjml-php](https://github.com/shyim/mjml-php) | PHP only (`ext-dom`, `ext-libxml`) |
| `false` | [spatie/mjml-php](https://github.com/spatie/mjml-php) | Node 16+ and the `mjml` npm package |

There is no silent fallback between the two modes.

## When Node starts

Fluent setters never start Node. Node starts only when `mjml.use_php_renderer` is `false` and one of these convert methods runs: `toHtml()`, `convert()`, `canConvert()`, `canConvertWithoutErrors()`. That path executes local `node mjml.mjs`.

`sidecar()` plus a convert call uses Spatie Sidecar (AWS), not local Node. It needs `spatie/mjml-sidecar` and `mjml.use_php_renderer=false`. This package does not require the sidecar package.

## Node detection

Spatie locates the `node` binary with Symfony `ExecutableFinder`. It prepends `getenv('MJML_NODE_PATH')` when that variable is set, then `/usr/local/bin` and `/opt/homebrew/bin`. It does **not** read Laravel `config()` or `env('MJML_NODE_PATH')` through this package. `MJML_NODE_PATH` must be a process environment variable (directory that contains the `node` binary), which matters for PHP-FPM / Herd where nvm is not on `PATH`.

Install the npm package next to Spatie:

```bash
cd vendor/spatie/mjml-php && npm install
```

`npm install mjml` at the application root is not enough: Spatie runs `mjml.mjs` from `vendor/spatie/mjml-php/bin` and resolves `mjml` from that package's `node_modules`.

## Capability matrix

Values: **yes** (same behaviour), **mapped** (forwarded onto shyim options), **exception** (`CouldNotRenderMjml`), **limited** (available, but the result shape differs).

| Method / option | PHP renderer (shyim) | Node renderer (Spatie) |
| --- | --- | --- |
| `toHtml`, `convert`, `canConvert`, `canConvertWithoutErrors` | yes | yes |
| `minify`, `beautify`, `keepComments` / `hideComments`, `validationLevel`, `filePath`, `ignoreIncludes` | mapped | yes |
| `sidecar` | exception | yes (`spatie/mjml-sidecar` is optional) |
| `workingDirectory` | exception | yes (directory that contains `mjml.mjs`) |
| `MjmlResult::array()` | limited (always `[]`) | yes (Spatie JSON AST) |
| `MjmlResult::raw()` / `errors()` | mapped (HTML plus shyim errors) | yes (full Spatie result) |

The full method and option list for shyim vs Spatie vs this proxy is in [docs/engine-api.md](docs/engine-api.md).
