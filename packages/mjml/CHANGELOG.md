# Changelog

## Unreleased

- Proxy the Spatie mjml-php API through `Moox\Mjml\Mjml` (`convert`, `canConvert`, `canConvertWithoutErrors`, minify/beautify, validation, and related options).
- Map overlapping options onto shyim when the PHP renderer is selected; `sidecar()` and `workingDirectory()` stay Node-only.
- Keep callers on Moox types (`MjmlResult`, `MjmlError`, `ValidationLevel`) instead of Spatie or shyim.
- Add a Moox `Mjml::new()->toHtml()` API.
- Render with shyim/mjml-php by default (`mjml.use_php_renderer`).
- Keep Spatie mjml-php as the Node engine when the PHP renderer is disabled.
- Fail with `CouldNotRenderMjml` instead of falling back between engines.
- Cover the public `Moox\Mjml\Mjml` API once per engine in Pest; skip Node only when the Node binary or Spatie `mjml` install is missing.
- Convert login and invoice fixtures with minify, comments, includes, and validation skip/soft/strict on both engines.
- Keep the step-by-step engine check in `docs/manual-check.md`.
- List shyim vs Spatie methods and options in `docs/engine-api.md`.

We currently don't track older changes in this package. Please refer to the [Moox Monorepo](https://github.com/mooxphp/moox) for the latest changes.
