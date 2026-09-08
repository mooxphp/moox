# Moox Mail Template

Filament editor for outbound MJML mail templates. One template entity, translated content per locale (Astrotomic / Moox Draft). The parent stores `slug`, `layout`, and an optional logo override. Translations store `title` (used as the mail subject), `brand_name`, `mail_content`, and `footer`.

Rendering uses `spatie/mjml-php` (via `moox/mjml`). This package does not log sent mail or talk to Microsoft Graph.

## Installation

```bash
composer require moox/mail-template
php artisan vendor:publish --tag=mail-template-migrations
php artisan migrate
```

Or run `php artisan mooxmail-template:install`.

Register the plugin on the Filament panel:

```php
use Moox\MailTemplate\Plugins\MailTemplatePlugin;

MailTemplatePlugin::make(),
```

The host app must provide the `mjml` npm package and Node 16+.

Language switching uses the Filament language selector (`$this->lang`). There is no locale field on the form.

## Layouts

Consuming packages merge Blade layouts into `config('mail-template.layouts')`:

```php
config([
    'mail-template.layouts' => array_merge(config('mail-template.layouts', []), [
        'theme-heco::emails.login-link' => 'Login-Link',
    ]),
]);
```

## Rendering

```php
use Moox\MailTemplate\Support\MailTemplateRenderer;

$renderer = app(MailTemplateRenderer::class);
$template = $renderer->find('login', 'de');

$html = $renderer->toHtml($template, [
    'user' => $user,
    'magicLink' => $url,
]);
```

`find($slug, $locale)` loads one parent row and applies the translation for `$locale` (or the first available translation). Blade produces the layout. If the output starts with `<mjml`, Spatie converts it to HTML; otherwise the Blade HTML is sent as-is.

In the Mail Templates list, **Preview** opens a new tab with the saved HTML. Tokens from `mail-template.preview_variables` are highlighted.
