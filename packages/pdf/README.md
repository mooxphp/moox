# Moox PDF

Renders PDF documents through a stable Moox API. Callers compose documents themselves; this package only draws them with [TCPDF](https://tcpdf.org/) 7.

There is no Blade view, no HTML-to-PDF pipeline, no Filament UI, and no routes. A document is a PHP class that implements `PdfDocument` and paints onto `PdfCanvas`. TCPDF stays inside the engine.

Callers should depend on `Moox\Pdf\*` only: `Pdf`, `Contracts\PdfCanvas`, `Contracts\PdfDocument`, and `Exceptions\CouldNotRenderPdf`. Do not import TCPDF types.

This package **writes** PDFs. It does not extract text from existing files (`moox/pdf-parser`), validate PDF/A (`moox/verapdf`), or embed ZUGFeRD XML (`moox/zugferd`).

## Features

<!--features-->

- `Pdf::new()->render($document)->output()` — PDF binary
- `Pdf::new()->render($document)->inline('name.pdf')` — Laravel response (`Content-Type: application/pdf`, inline disposition)
- `PdfDocument` / `PdfCanvas` contracts so host layouts never touch TCPDF
- Canvas: page, font, colour, text, MultiCell, image, SVG, rotation, line, HTML, simple table
- UTF-8 A4 portrait, millimetres
- Core 14 fonts plus DejaVu Sans (bundled under `resources/fonts`)
- TCPDF 7 file allowlist via `config/pdf.php` (`allowed_paths`)
- Failures wrapped as `CouldNotRenderPdf`

<!--/features-->

## Responsibility boundaries

| Layer | Owns |
| --- | --- |
| `moox/pdf` | Engine, canvas, fonts, allowlist, `Pdf` facade-style class |
| Host app or theme package | Layout classes, copy, data DTOs, logos, preview routes, HTTP controllers |
| `moox/pdf-parser` | Reading text out of an existing PDF |
| `moox/zugferd` | EN 16931 XML and PDF/A-3 merge |
| `moox/verapdf` | PDF/A validation |

Keep layout out of this package. If a product sheet, letter, or invoice looks wrong, the document class in the host is the place to change it.

## Requirements

| Requirement | Purpose |
| --- | --- |
| PHP 8.2+ (via `moox/core`) | Package code is strict-typed PHP 8 |
| Laravel 11–13 (via `moox/core`) | Service provider, config, `Response` |
| `moox/core` | `MooxServiceProvider`, installer metadata |
| `tecnickcom/tcpdf` ^7.0 | Drawing engine |

No extra binaries. No Java, Ghostscript, Chromium, or `wkhtmltopdf`.

## Installation

```bash
composer require moox/pdf
```

Laravel auto-discovers `Moox\Pdf\PdfServiceProvider`. The provider registers the bundled font path (`K_PATH_FONTS`) when the package is registered.

Optional, if you use the Moox installer in a full Moox app:

```bash
php artisan moox:install
```

Publish a local config copy when the host needs extra asset directories (logo, signature, product photos):

```bash
php artisan vendor:publish --tag=pdf-config
```

That writes `config/pdf.php`. The only key is `allowed_paths` (see [File allowlist](#file-allowlist)).

### Monorepo / Devlink

In a Moox host that uses `moox/devlink`, enable the package and point Composer at the local path:

```php
// config/devlink.php
'pdf' => [
    'active' => true,
    'path' => $public_base_path.'/pdf',
    'type' => 'public',
],
```

```json
{
    "type": "path",
    "url": "packages/pdf",
    "options": {
        "symlink": true,
        "versions": { "moox/pdf": "dev-main" }
    }
}
```

Then:

```bash
php artisan moox:devlink
composer update moox/pdf
```

The host `composer.json` should require `"moox/pdf": "*"`.

## How to use it

1. Put images and SVGs in a directory the host controls (theme package, `storage/`, `resources/`).
2. Append that directory to `config('pdf.allowed_paths')` **before the first** `Pdf::render()` in the process. A service provider `boot` / `packageBooted` method is the usual place.
3. Build a data object (array, DTO, Eloquent model — this package does not care).
4. Implement `PdfDocument` in the host. `draw(PdfCanvas $canvas)` is the layout.
5. Call `Pdf::new()->render($document)->output()` or `inline()`.

A typical host layout:

```
app/Pdf/Data/OfferLetterData.php      DTO / factory
app/Pdf/Layout/OfferLetter.php        implements PdfDocument
app/Http/Controllers/Pdf/OfferController.php
```

Assets can live next to a theme:

```
packages/theme-acme/resources/pdf/logo.svg
packages/theme-acme/src/Pdf/AcmePdfAssets.php
```

`AcmePdfAssets` returns absolute paths. The theme service provider registers the directory on the allowlist. The document class only receives those paths through the DTO.

Do not:

- `use TCPDF` or `use Tecnick\...` from host code
- put layout classes under `Moox\Pdf`
- call `image()` / `svg()` with a path that is not under an allowed directory
- expect Blade, CSS flexbox, or a browser engine
- treat `html()` as a full HTML/CSS renderer (it is TCPDF `writeHTML`)

## Render

```php
use Moox\Pdf\Contracts\PdfCanvas;
use Moox\Pdf\Contracts\PdfDocument;
use Moox\Pdf\Pdf;

$document = new class implements PdfDocument
{
    public function draw(PdfCanvas $canvas): void
    {
        $canvas
            ->setFont('dejavusans', '', 12)
            ->text($canvas->leftMargin(), 40, 'Hello');
    }
};

$binary = Pdf::new()->render($document)->output();

return Pdf::new()->render($document)->inline('document.pdf');
```

`render()` stores the document. `output()` and `inline()` create a fresh TCPDF instance and call `draw()`. Calling `output()` twice renders twice.

| Method | Result |
| --- | --- |
| `output(): string` | PDF bytes, starting with `%PDF-` |
| `inline(string $filename = 'document.pdf'): Response` | HTTP 200, `Content-Type: application/pdf`, `Content-Disposition: inline` |

There is no `download()` helper. For an attachment:

```php
$bytes = Pdf::new()->render($document)->output();

return response($bytes, 200, [
    'Content-Type' => 'application/pdf',
    'Content-Disposition' => 'attachment; filename="offer.pdf"',
]);
```

To store a file:

```php
Storage::disk('local')->put('offers/'.$id.'.pdf', Pdf::new()->render($document)->output());
```

`inline()` uses `basename($filename)` and strips `"`, CR, and LF from the name.

`output()` before `render()` throws `CouldNotRenderPdf` (`Call render() with a PdfDocument before output().`).

## Defaults

Each render starts from the same TCPDF document. The canvas can change margins, fonts, and page breaks; it cannot change paper size, orientation, or metadata.

| Setting | Value |
| --- | --- |
| Orientation | Portrait (`P`) |
| Unit | millimetres |
| Format | A4 (210 × 297 mm) |
| Encoding | UTF-8 |
| Header / footer templates | off |
| Creator | `moox/pdf` |
| Title | `PDF` |
| Margins | left 20, top 16, right 18, bottom 0 |
| Auto page break | off |
| First page | already added |
| Initial font | `dejavusans`, 10 pt |

Origin is the top-left of the page. `x()` / `y()` are the current TCPDF cursor. `width()` / `height()` are the full page, including margins.

Content width is usually:

```php
$width = $canvas->width() - $canvas->leftMargin() - $canvas->rightMargin();
```

## PdfDocument

```php
namespace Moox\Pdf\Contracts;

interface PdfDocument
{
    public function draw(PdfCanvas $canvas): void;
}
```

One class per document type. Inject data through the constructor. Keep `draw()` as the orchestration (header, body, footer) and split helpers for repeated blocks.

```php
final class OfferLetter implements PdfDocument
{
    public function __construct(private OfferLetterData $data) {}

    public function draw(PdfCanvas $canvas): void
    {
        $canvas->setMargins(20, 16, 18, 20);
        $this->drawHeader($canvas);
        $this->drawBody($canvas);
        $this->drawFooter($canvas);
    }
}
```

Anonymous classes are fine in tests. Production layouts should be named classes.

## PdfCanvas

All measurements are millimetres. Colour channels are 0–255 RGB. Methods that draw return `$this` and can be chained.

### Page geometry

| Method | Meaning |
| --- | --- |
| `width(): float` | Page width |
| `height(): float` | Page height |
| `leftMargin(): float` | Current left margin |
| `rightMargin(): float` | Current right margin |
| `x(): float` | Cursor X |
| `y(): float` | Cursor Y |
| `remainingHeight(): float` | Distance from cursor to bottom margin |
| `setPosition(float $x, float $y): self` | Move cursor |
| `setMargins(float $left, float $top, float $right, float $bottom = 0): self` | Margins; also turns auto page break off and uses `$bottom` as the break margin |
| `setAutoPageBreak(bool $enabled, float $margin = 0): self` | TCPDF automatic page breaks |
| `setCellPaddings(float $left, float $top, float $right, float $bottom): self` | Padding inside MultiCell / table cells |
| `stringHeight(float $width, string $text): float` | Height TCPDF would use for that string at the current font |

`setMargins()` is the right way to reserve a footer band: pass the footer height as `$bottom`, then either break pages yourself with `remainingHeight()` or enable auto page break.

### Font and colour

```php
$canvas
    ->setFont('dejavusans', 'B', 13.5)
    ->setTextColor(0, 93, 157)
    ->setDrawColor(150, 150, 150)
    ->setFillColor(242, 242, 242)
    ->setLineWidth(0.25);
```

| `setFont($family, $style, $size)` | |
| --- | --- |
| `$family` | See [Fonts](#fonts) |
| `$style` | `''`, `B`, `I`, `U`, or combinations (`BI`, `BU`) |
| `$size` | Points, not millimetres |

`table()` always switches the font to DejaVu Sans. Set the font again after a table if the next block needs a different family.

### Text

```php
$canvas->text($x, $y, 'Single line');

$canvas
    ->setPosition($left, $y)
    ->multiCell($width, 5.4, $paragraph, [
        'align' => 'L',   // L C R J, or left/center/right/justify
        'fill' => false,
        'border' => 0,    // 0, 1, or TCPDF border string (LTRB)
        'valign' => 'T',  // T M B
    ]);
```

`text()` does not wrap. `multiCell()` wraps, advances the cursor, and uses the current X/Y as the origin.

For mixed German text, use DejaVu Sans. Core fonts (Helvetica, Times, Courier) are WinAnsi and will mangle umlauts.

### Lines, images, SVG, rotation

```php
$canvas->line($x1, $y1, $x2, $y2);

$canvas->image($absoluteJpgOrPng, $x, $y, $width, $height = 0);
$canvas->svg($absoluteSvg, $x, $y, $width, $height = 0);

$canvas->rotate(50, $x, $y, function (PdfCanvas $rotated) use ($x, $y, $label): void {
    $rotated->text($x, $y, $label);
});
```

`image()` embeds at 300 DPI. If `$height` is `0`, TCPDF keeps the aspect ratio from `$width`. Paths must be real files on an allowed path.

`rotate()` wraps TCPDF `StartTransform` / `Rotate` / `StopTransform`. Draw only inside the callback.

### Pages

| Method | Meaning |
| --- | --- |
| `addPage(): self` | New page, same format |
| `page(): int` | Current 1-based page |
| `pageCount(): int` | Number of pages so far |
| `usePage(int $page): self` | Jump to an existing page (footers after the body) |
| `pageAlias(): string` | TCPDF alias for the current page number |
| `totalPagesAlias(): string` | TCPDF alias for the total page count |

Default auto page break is **off**. Either:

- measure with `stringHeight()` / `remainingHeight()` and call `addPage()` yourself, or
- `setAutoPageBreak(true, $bottomMargin)` for `html()` and other flowing content.

A footer after the body is known:

```php
$total = $canvas->pageCount();

for ($page = 1; $page <= $total; $page++) {
    $canvas->usePage($page);
    $canvas->text($canvas->leftMargin(), $canvas->height() - 10, $page.'/'.$total);
}
```

`pageAlias()` / `totalPagesAlias()` are useful in `table()` page-break hooks, where the final page count is not known yet. Prefer `page()` / `pageCount()` plus `usePage()` when you stamp footers at the end: the aliases stay in the PDF as `{pnb}` / `{nb}` if TCPDF does not substitute them.

### HTML

```php
$canvas
    ->setAutoPageBreak(true, 16)
    ->setFont('dejavusans', '', 12)
    ->html('<p>Hello <b>TCPDF</b></p><ul><li>Item</li></ul>');
```

This is TCPDF `writeHTML`, not a browser. Supported-ish: paragraphs, bold/italic, simple tables, lists, `<img src="absolute-path">`, a subset of inline CSS. Flex, grid, webfonts, and modern CSS will not work.

Turn auto page break on before long HTML. With the default (`false`), overflowing HTML is clipped.

For mixed HTML and images, split the markup and call `html()` per fragment so you can `addPage()` between them. The host app owns that splitting.

## Tables

```php
$canvas
    ->setPosition($canvas->leftMargin(), $canvas->y())
    ->table(
        [
            ['Art', 'Menge', 'Preis'],
            ['Rohr', '10', '12,50'],
            ["max. 8 bar\noptional 15 bar", '2', '4,00'],
        ],
        [
            'header' => true,
            'widths' => [80, 30, 30],
            'fontSize' => 8,
            'lineHeight' => 5,
            'border' => 0, // 0 = no grid; zebra still paints fills
            'align' => ['L', 'R', 'R'], // string, per column, or per row/column
            'headerAlign' => 'C',
            'headerFill' => [0, 93, 157],
            'headerColor' => [255, 255, 255],
            'fill' => [255, 255, 255],
            'altFill' => [242, 242, 242],
            'textColor' => [29, 29, 27],
            'beforePageBreak' => function (PdfCanvas $canvas): void {
                // e.g. draw a page number on the page that is about to close
            },
            'afterPageBreak' => function (PdfCanvas $canvas): void {
                $canvas->setPosition($canvas->leftMargin(), 16);
            },
        ],
    );
```

Behaviour that is easy to miss:

- Rows are `list<list<string>>`. The first row is the header when `'header' => true`.
- Missing `widths` fall back to 30 mm per column. Pass widths that sum to the content width.
- `\n` inside a cell becomes extra **visual rows**. Zebra striping (`altFill`) counts those visual rows, not the original data rows. Completely blank visual rows do not advance the zebra index.
- When a data row does not fit `remainingHeight()`, the engine adds a page, runs the hooks, and reprints the header.
- Header cells use DejaVu Sans Bold; body cells use regular DejaVu Sans at `fontSize`.
- Alignment tokens: `L`/`LEFT`, `C`/`CENTER`, `R`/`RIGHT`, `J`/`JUSTIFY`.

`align` shapes:

```php
'align' => 'L';                          // every cell
'align' => ['L', 'R', 'R'];              // per column
'align' => [                             // per row, then column
    ['C', 'C', 'C'],
    ['L', 'R', 'R'],
];
```

## Fonts

TCPDF 7 loads definitions from `K_PATH_FONTS`. The service provider sets that constant to `packages/pdf/resources/fonts` (the real path of this package).

| Family key | Files | Unicode |
| --- | --- | --- |
| `dejavusans` | Regular, Bold, Oblique, BoldOblique | yes — default for German |
| `helvetica`, `helveticab`, `helveticai`, `helveticabi` | Core 14 | WinAnsi |
| `times`, `timesb`, `timesi`, `timesbi` | Core 14 | WinAnsi |
| `courier`, `courierb`, `courieri`, `courierbi` | Core 14 | WinAnsi |
| `symbol`, `zapfdingbats` | Core 14 | symbol encodings |

Use `dejavusans` unless you have a reason not to. Core fonts are smaller, but they are the wrong choice for `äöüß`.

The package ships the converted JSON / `.z` files. You do not need TTF files at runtime.

### Regenerating font files

Only needed when updating DejaVu or the Core 14 metrics. Install `tecnickcom/tc-font-mirror` so the sources sit at:

`vendor/tecnickcom/tc-lib-pdf-font/util/vendor/tecnickcom/tc-font-mirror/`

The script looks for `vendor/` two directories above the package root (the Moox monorepo root). From that root:

```bash
php packages/pdf/bin/convert-fonts.php
```

That writes into `resources/fonts/core` and `resources/fonts/dejavu`. Commit the generated files with the package.

## File allowlist

TCPDF 7 refuses to read files outside `K_ALLOWED_PATHS`. The engine builds that list on the **first** `render()` in the PHP process and defines it as a constant. Later changes to config are ignored for that process.

Always allowed:

- this package root
- `resources/` and `resources/fonts/` inside the package
- the same paths after `realpath()` (needed when the package is a symlink / Devlink junction)

Host assets are **not** allowed until you add them.

Published `config/pdf.php`:

```php
return [
    'allowed_paths' => [
        // storage_path('app/pdf-assets'),
    ],
];
```

Appending at boot is the usual pattern, so a theme package can register itself without a published config:

```php
public function packageBooted(): void
{
    $paths = config('pdf.allowed_paths', []);
    if (! is_array($paths)) {
        $paths = [];
    }

    $paths[] = AcmePdfAssets::directory();
    config(['pdf.allowed_paths' => array_values(array_unique($paths))]);
}
```

Register paths before any `Pdf::render()`. A preview route in the same request is fine as long as providers have booted.

If `image()` or `svg()` throws `CouldNotRenderPdf` with a TCPDF path error, the file is missing or the directory is not on the list.

## Exceptions

`Moox\Pdf\Exceptions\CouldNotRenderPdf` extends `RuntimeException`.

| Cause | Message |
| --- | --- |
| `output()` / `inline()` without `render()` | `Call render() with a PdfDocument before output().` |
| Anything thrown from `draw()` | Original message, previous exception chained |
| Empty or non-PDF engine output | `TCPDF produced an empty or invalid PDF.` |

Host code can throw `CouldNotRenderPdf` from `draw()`; the engine rethrows it unchanged.

## What this API does not expose

These stay TCPDF-internal on purpose. Open an issue on the package if a layout cannot be done without them.

- Paper size and landscape
- PDF title, author, subject, keywords (Creator is always `moox/pdf`)
- Encryption, permissions, PDF/A
- Drawing circles, rectangles, clipping paths (use `line()`, filled `multiCell()`, or SVG)
- TrueType files from the host at runtime
- Streaming / chunked output

## Configuration

File: `config/pdf.php`. One key.

| Key | Type | Default |
| --- | --- | --- |
| `allowed_paths` | `list<string>` | `[]` |

No environment variables. No migrations, views, translations, or Artisan commands.

## Public API

| Kind | FQCN |
| --- | --- |
| Entry | `Moox\Pdf` |
| Document | `Moox\Pdf\Contracts\PdfDocument` |
| Canvas | `Moox\Pdf\Contracts\PdfCanvas` |
| Error | `Moox\Pdf\Exceptions\CouldNotRenderPdf` |
| Provider | `Moox\Pdf\PdfServiceProvider` |

`Moox\Pdf\Engines\TcpdfEngine` and `TcpdfCanvas` are the TCPDF adapter. Host code should not import them. Tests in this package may.

## Running tests

From the Moox monorepo root:

```bash
php vendor/bin/pest packages/pdf/tests
```

From a host that path-repositories the package (for example after `moox:devlink`):

```bash
php artisan test --compact tests/Unit/PdfRendererTest.php
```

The package tests render a fixture document, an inline response, a styled table, HTML with auto page break, and assert that public sources do not `use TCPDF`.

Host layout tests belong in the host (`Pdf::new()->render(new YourDocument($data))->output()` and assert `%PDF-`).

## See also

- [Moox documentation](https://moox.org/docs/pdf)
- [Moox PdfParser](../pdf-parser/README.md) — extract text from an existing PDF
- [Moox Zugferd](../zugferd/README.md) — embed invoice XML in PDF/A-3
- [Moox VeraPdf](../verapdf/README.md) — validate PDF/A

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

Thanks to so many [people for their contributions](https://github.com/mooxphp/moox#contributors) to this package.

TCPDF is Copyright Tecnick.

## License

The MIT License (MIT). Please see [our license and copyright information](https://github.com/mooxphp/moox/blob/main/LICENSE.md) for more information.
