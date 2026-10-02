# Changelog

## Unreleased

- Add a Moox `Pdf::new()->render($document)->output()` API backed by TCPDF. The public class is `Moox\Pdf\Pdf` in `src/Pdf.php`, like `Moox\Mjml\Mjml`.
- Keep callers on Moox types (`Moox\Pdf\Pdf`, `PdfCanvas`, `PdfDocument`, `CouldNotRenderPdf`) instead of TCPDF.
- Restrict package config to `allowed_paths`; callers own layout, data and preview routes.

We currently don't track older changes in this package. Please refer to the [Moox Monorepo](https://github.com/mooxphp/moox) for the latest changes.
