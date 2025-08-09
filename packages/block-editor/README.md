# Moox Block Editor

Early development of the Moox Block Editor

```bash

npm run dev

```

http://localhost:5173/

```bash

npm run build

```

https://mooxdev.test/moox/block-editor

## Todo

-   Context-aware Block tab: wire BlockNote selection and render controls for the selected block.
-   Persist UI: remember sidebar width and open panels.
-   Polish: replace custom tabs/accordion with shadcn primitives for a11y/animation.
-   Title UX: optional slug beneath title (read-only with edit button), like Filament.
-   Shortcuts: Cmd+S to Save; Esc to toggle sidebar.
-   Auto-Save etc.
-   https://laraveljsonapi.io/ OR https://github.com/timacdonald/json-api with the concept of https://filamentphp.com/plugins/rupadana-api-service to generate all default APIs we need like Taxonomies etc.
-   Blocks API also as JSON:API but specially for the Block Editor

## Learn from

Align the design with Filament.

![Filament](screenshot/filament.jpg)

Get most features from Gutenberg.

![Gutenberg](screenshot/gutenberg.jpg)
