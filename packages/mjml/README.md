# Moox MJML

Converts MJML markup to HTML through a stable Moox API. Packages that send mail compose MJML themselves; this package only renders.

Two engines are required and selected by config. There is no silent fallback: a failure of the selected engine is raised as an exception.

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

Callers should depend on `Moox\Mjml\Mjml` (and the Moox result, error, and enum types) only. Do not import Spatie or shyim types.

The fluent methods follow the Spatie mjml-php surface: `keepComments()`, `hideComments()`, `ignoreIncludes()`, `beautify()`, `minify()`, `validationLevel()`, `filePath()`, `workingDirectory()`, and `sidecar()`. `toHtml()` and `convert()` also accept an options array as the second argument; array values override fluent ones.

Options that were not set keep each engine's own defaults, so `toHtml($mjml)` without options stays as it is today.

### Engine mapping

| Method / option | PHP renderer (shyim) | Node renderer (Spatie) |
| --- | --- | --- |
| `toHtml`, `convert`, `canConvert`, `canConvertWithoutErrors` | yes | yes |
| `minify`, `beautify`, `keepComments` / `hideComments`, `validationLevel`, `filePath`, `ignoreIncludes` | mapped to shyim `MjmlOptions` | forwarded to Spatie |
| `sidecar` | exception | forwarded to Spatie (`spatie/mjml-sidecar` is optional) |
| `workingDirectory` | exception | forwarded to Spatie (path to `mjml.mjs`) |
| `MjmlResult::array()` / `raw()` | HTML plus shyim validation errors; no Spatie JSON AST | full Spatie result |

## Config

`mjml.use_php_renderer` (env `MJML_USE_PHP_RENDERER`, default `true`):

| Value | Engine | Runtime |
| --- | --- | --- |
| `true` | [shyim/mjml-php](https://github.com/shyim/mjml-php) | PHP only (`ext-dom`, `ext-libxml`) |
| `false` | [spatie/mjml-php](https://github.com/spatie/mjml-php) | Node 16+ and the `mjml` npm package |

When using the Node engine:

```bash
npm install mjml
```

If PHP-FPM cannot see Node on `PATH`, set `MJML_NODE_PATH` to the directory that contains the `node` binary, and install the npm package next to Spatie:

```bash
cd vendor/spatie/mjml-php && npm install
```

Sidecar rendering needs `spatie/mjml-sidecar` and `mjml.use_php_renderer=false`. This package does not require the sidecar package.
