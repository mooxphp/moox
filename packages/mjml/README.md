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
use Moox\Mjml\Mjml;

$html = Mjml::new()->toHtml($mjml);
```

Callers should depend on `Moox\Mjml\Mjml` only. Do not import Spatie or shyim types.

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
