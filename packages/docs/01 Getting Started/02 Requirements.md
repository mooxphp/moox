---
title: Requirements
description: System and version requirements for Moox (Laravel, PHP, Filament, WordPress).
---

# Requirements

For the current Moox version we recommend:

- [Laravel](https://laravel.com/) 12.0 or higher
- [PHP](https://php.net) 8.3 or higher
- A Laravel-compatible database

We suggest to use Redis.

For Moox Press only:

- WordPress 6.5 or higher
- MySQL, MariaDB or compatible

Additional requirements may apply per package; the list above covers the most common dependencies.

## Compatibility matrix

| Moox | Laravel | Filament | PHP  | WordPress | Tailwind | Livewire |
| ---- | ------- | -------- | ---- | --------- | -------- | -------- |
| 2.x  | ^9.x    | 2.x      | ^8.0 | ^5        | v3       | v2       |
| 3.x  | ^10.x   | 3.x      | ^8.1 | ^6        | v3       | v3       |
| 4.x  | ^11.x   | 4.x      | ^8.2 | ^6        | v4       | v3       |
| 5.x  | ^12.x   | 5.x      | ^8.3 | ^6.5      | v4       | v4       |

## Known compatibilities

We are compatible with [Laravel Forge](https://forge.laravel.com) and different cloud providers like Hetzner and AWS, and with [Laravel Cloud](https://cloud.laravel.com). For smaller projects with [Envoyer](https://envoyer.io) and shared hosting Moox Jobs supports database queues, and provides a hosting feature to schedule jobs with or without CRON on that server.

For local development we support [Laravel Herd](https://herd.laravel.com), as well as [VS Code](../12%20Development/06%20VS%20Code.md) and [Cursor](../12%20Development/07%20Cursor.md), what is also equipped with AI support amongst others by our Laravel Boost implementation and the [Moox MCP](https://github.com/mooxphp/mcp).

## Known incompatibilities

Moox should play well with many Laravel packages and Filament plugins, but in some places (like managing Media, Localization) we are pretty opinionated, so replacements will not simply work.
