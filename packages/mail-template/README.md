# moox Mail Template

Filament editor for outbound MJML mail templates. One template entity, translated content per locale (Astrotomic / moox Draft). The parent stores `slug`, a relation to a **mail layout**, and an optional logo override. Translations store `title` (used as the mail subject), `mail_content`, and `footer`. Branding (`brandName`) comes from the theme / `app.name`, not from the template.

Layouts are a second Draft entity in this package: `slug` on the parent, translated `title`, optional `logo`, and optional `footer`. Templates pick a layout from the database. Empty logo/footer on the template fall back to the layout for the same locale.

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

Mail layouts are edited in Filament. The MJML shell is `config('mail-template.view')`, default `mail-template::emails.layout`. A theme may override that view when this package is installed:

```php
if (class_exists(\Moox\MailTemplate\MailTemplateServiceProvider::class)) {
    config(['mail-template.view' => 'theme-heco::emails.layout']);
}
```

## Rendering

```php
use Moox\MailTemplate\Support\MailTemplateRenderer;

$renderer = app(MailTemplateRenderer::class);
$template = $renderer->find('login', 'de_DE');

$html = $renderer->toHtml($template, [
    'user' => $user,
    'magicLink' => $url,
]);
```

`find($slug, $locale)` loads one parent row and applies the translation for `$locale` (or the first available translation). Blade produces the layout. If the output starts with `<mjml`, Spatie converts it to HTML; otherwise the Blade HTML is sent as-is.

In the Mail Templates list, **Preview** opens a new tab with the saved HTML. Tokens from `mail-template.preview_variables` are highlighted.
