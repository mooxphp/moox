# Moox MJML

Thin Moox package that requires [`spatie/mjml-php`](https://github.com/spatie/mjml-php). MJML templates live in the packages that send mail, not here.

Iteration 1 converts MJML to HTML with Spatie's API (Node + the `mjml` npm package on each send). A later iteration can wrap Spatie and `shyim/mjml-php`.

## Installation

```bash
composer require moox/mjml
npm install mjml
```

Node 16 or newer must be available where mail is rendered.

## Usage

```php
use Spatie\Mjml\Mjml;

$html = Mjml::new()->toHtml($mjml);
```

See the [Spatie README](https://github.com/spatie/mjml-php).
