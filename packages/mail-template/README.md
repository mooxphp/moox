# moox Mail Template

Filament editor for outbound MJML mail templates. One template entity, translated content per locale (Astrotomic / moox Draft). The parent stores `slug`, a relation to a **mail layout**, and an optional logo override. Translations store `title` (used as the mail subject), `mail_content`, and `footer`. Branding (`brandName`) comes from the theme / `app.name`, not from the template.

Layouts are a second Draft entity in this package: `slug` and optional `logo` on the parent, translated `title` and optional `footer`. Templates pick a layout from the database. Empty logo/footer on the template fall back to the layout. If both are empty, the mail is rendered without a logo or footer.

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

Mail layouts are edited in Filament. Colors live on the layout parent (`background_color`, `button_color`, `text_color`). Logo and footer fall back from template to layout; if both are empty, those blocks are omitted.

`MjmlDocumentComposer` builds the MJML document in PHP: `mj-head` with the layout colors, then `mj-body` with an optional logo, the template content, and an optional footer. Content and footer fragments that already contain `<mj-section` are injected as body siblings; otherwise they are wrapped in `mj-section` / `mj-column`.

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

`find($slug, $locale)` loads one parent row and applies the translation for `$locale` (or the first available translation). `{…}` tokens in `mail_content` and `footer` are interpolated, then `MjmlDocumentComposer` produces the MJML string. If the output starts with `<mjml`, Spatie converts it to HTML.

In the Mail Templates list, **Preview** opens a new tab with the saved HTML. Tokens from `mail-template.preview_variables` are highlighted.
