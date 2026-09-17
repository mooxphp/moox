# Engine API comparison

Taken from the installed packages: [shyim/mjml-php](https://github.com/shyim/mjml-php) `^0.2` (0.2.1) and [spatie/mjml-php](https://github.com/spatie/mjml-php) `^1.2` (1.3.0).

Callers should use `Moox\Mjml\*` only. This file lists what each engine exposes and what the proxy forwards.

## Class methods

| Method | Spatie | shyim | Moox proxy |
| --- | --- | --- | --- |
| `new()` | yes | no (`new Mjml($options, $cache)`) | yes |
| `toHtml($mjml, $options = [])` | string | `MjmlResult` (no options array) | string |
| `convert($mjml, $options = [])` | `MjmlResult` | no | `MjmlResult` |
| `render($mjml, $options, $components)` | no | static, returns `MjmlResult` | no (PhpRenderer calls `render()` internally) |
| `canConvert($mjml)` | yes | no | yes |
| `canConvertWithoutErrors($mjml)` | yes | no | yes |
| `keepComments($bool = true)` | yes | `MjmlOptions` only | yes, mapped |
| `hideComments()` | yes (`keepComments(false)`) | no | yes, mapped |
| `ignoreIncludes($bool = true)` | yes | `MjmlOptions` only | yes, mapped |
| `beautify($bool = true)` | yes | `MjmlOptions` only | yes, mapped |
| `minify($bool = true)` | yes | `MjmlOptions` only | yes, mapped |
| `validationLevel($level)` | yes | `MjmlOptions` only | yes, mapped |
| `filePath($path)` | yes | `MjmlOptions` only | yes, mapped |
| `workingDirectory($path)` | yes (folder that contains `mjml.mjs`) | no | PHP throws `CouldNotRenderMjml` |
| `sidecar($bool = true)` | yes | no | PHP throws `CouldNotRenderMjml` |
| `registerComponent($class)` | no | yes | not exposed |
| `addHook($hook)` | no | yes (`PipelineHooks`) | not exposed |
| Cache in constructor | no | yes (`NodeCacheInterface`) | not exposed |

Spatie also accepts any official [MJML Node.js options](https://github.com/mjmlio/mjml#inside-nodejs) as the second argument of `toHtml()` / `convert()`. `mjml.mjs` passes that array to `mjml2html(mjml, options)`. Many of those keys have no fluent setter.

## Render options and defaults

| Option | Spatie default | shyim default | Moox PHP | Moox Node |
| --- | --- | --- | --- | --- |
| `keepComments` | `true` | `true` | mapped | forwarded |
| `ignoreIncludes` | `false` (includes on) | `true` (includes off) | mapped; shyim default `true` once an options object is built | forwarded |
| `beautify` | `false` | `false` | mapped | forwarded |
| `minify` | `false` | `false` | mapped | forwarded |
| `validationLevel` | `soft` | `strict` | mapped; shyim default `strict` once an options object is built | forwarded; engine default `soft` |
| `filePath` | `'.'` | `null` | mapped | forwarded |
| `fonts` | Node MJML default | Open Sans, Droid Sans, Lato, Roboto, Ubuntu | not set | options array only |
| `language` | — | `'und'` | not set | — |
| `dir` | — | `'auto'` | not set | — |
| `includePath` | — | `null` | not set | — |
| `juiceOptions` / `juicePreserveTags` / `minifyOptions` / `preprocessors` | Node MJML, via array | no | no | options array to Node |
| `sidecar` | `false` | — | exception | yes |
| `workingDirectory` | `vendor/spatie/mjml-php/bin` | — | exception | yes |

Empty options on Moox PHP pass `null` into shyim, so shyim defaults apply (`strict`, includes off). As soon as any fluent option is set, `PhpRenderer` builds a `MjmlOptions` object and uses shyim constructor defaults (`ignoreIncludes: true`, `validationLevel: Strict`) unless those keys are present in `$options`.

`ValidationLevel` values are the same on both engines: `skip`, `soft`, `strict`. Strict throws (`ValidationException` in shyim, `CouldNotConvertMjml` in Spatie). Soft collects errors on the result. Skip does not validate.

## Result objects

| Accessor | Spatie `MjmlResult` | shyim `MjmlResult` | Moox `MjmlResult` |
| --- | --- | --- | --- |
| HTML | `html()` | `$html` | `html()` |
| AST | `array()` (JSON AST) | `$ast` (`Node`) | `array()`; PHP always `[]` |
| Raw payload | `raw()` | no `raw()` | `raw()` |
| Errors | `errors()` / `hasErrors()` | `$errors` (`ValidationError`) | `errors()` / `hasErrors()` |

Spatie `MjmlError`: `line()`, `message()`, `tagName()`, `formattedMessage()`, `toArray()`.

shyim `ValidationError`: `$message`, `$tagName`, `$line`, `$type`, plus `__toString()`.

Moox maps shyim errors onto the Spatie shape (`line` / `message` / `tagName`). `$type` is dropped.

## shyim only

- Custom components (`registerComponent`)
- Pipeline hooks (`beforeParse`, `afterParse`, `afterHeadProcessed`, `afterBodyRendered`, `afterPostProcess`)
- AST cache
- `language` / `dir` on the HTML root
- `includePath` (extra allowed include roots; includes are off by default)
- CLI `mjml-php` (`--process-includes`, `--include-path`, `--lang`, `--dir`, …)

## Spatie only

- Spatie-style fluent API
- `canConvert` / `canConvertWithoutErrors`
- JSON AST via `array()`
- `workingDirectory` (local Node)
- `sidecar` (AWS Lambda, extra package `spatie/mjml-sidecar`)
- Node-only MJML options (`juiceOptions`, preprocessors, …)
