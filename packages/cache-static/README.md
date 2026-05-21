# Moox Cache Static

Optional extension for [moox/cache](https://github.com/mooxphp/moox) that registers page-cache targets for [Joseph Silber Page Cache](https://github.com/JosephSilber/page-cache).

## Installation

```bash
composer require moox/cache-static
```

Register the main cache plugin and ensure this provider is auto-discovered.

## Targets

- `page-cache-clear-all` — `page-cache:clear`
- `page-cache-clear-slug` — `page-cache:clear {slug}` with optional `--recursive`
