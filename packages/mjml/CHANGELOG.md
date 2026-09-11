# Changelog

## Unreleased

- Add a Moox `Mjml::new()->toHtml()` API.
- Render with shyim/mjml-php by default (`mjml.use_php_renderer`).
- Keep Spatie mjml-php as the Node engine when the PHP renderer is disabled.
- Fail with `CouldNotRenderMjml` instead of falling back between engines.

We currently don't track older changes in this package. Please refer to the [Moox Monorepo](https://github.com/mooxphp/moox) for the latest changes.
