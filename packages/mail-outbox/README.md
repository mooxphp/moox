# Moox Mail Outbox

Filament editor for outbound MJML mail templates: Blade view, logo upload, footer, and optional `mail_content` fragments.

This iteration does not log sent mail or talk to Microsoft Graph. Rendering uses `spatie/mjml-php` (via `moox/mjml`).

## Installation

```bash
composer require moox/mail-outbox
php artisan vendor:publish --tag=mail-outbox-migrations
php artisan migrate
```

Register the plugin on the Filament panel:

```php
use Moox\MailOutbox\Plugins\MailOutboxPlugin;

MailOutboxPlugin::make(),
```

The host app must provide the `mjml` npm package and Node 16+.

In the Mail Templates list, **Preview** opens a new tab with the HTML that Spatie would send (saved record only, no dummy payload).

## Rendering

```php
use Moox\MailOutbox\Support\MailTemplateRenderer;

$html = app(MailTemplateRenderer::class)->toHtml($template, [
    'user' => $user,
    'magicLink' => $url,
]);
```

Blade first produces MJML (including `{!! $mailContent !!}` and `{!! $footer !!}` from the database). Spatie then converts that MJML to HTML.
