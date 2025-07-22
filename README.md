<p align="center">
    <br>
  	<img src="packages/brand/public/logo/moox-logo.png" width="200" alt="Moox Logo">
    <br>
</p><br>

<p align="center">
    <a href="https://github.com/mooxphp/moox/actions/workflows/pest.yml"><img alt="PEST Tests" src="https://github.com/mooxphp/moox/actions/workflows/pest.yml/badge.svg"></a>
    <a href="https://github.com/mooxphp/moox/actions/workflows/pint.yml"><img alt="Laravel PINT PHP Code Style" src="https://github.com/mooxphp/moox/actions/workflows/pint.yml/badge.svg"></a>
    <a href="https://github.com/mooxphp/moox/actions/workflows/phpstan.yml"><img alt="PHPStan Level 5" src="https://github.com/mooxphp/moox/actions/workflows/phpstan.yml/badge.svg"></a>
</p>
<p align="center">
    <a href="https://www.tailwindcss.com"><img alt="TailwindCSS 3" src="https://img.shields.io/badge/TailwindCSS-v3-orange?logo=tailwindcss&color=06B6D4"></a>
    <a href="https://www.alpinejs.dev"><img alt="AlpineJS 3" src="https://img.shields.io/badge/AlpineJS-v3-orange?logo=alpine.js&color=8BC0D0"></a>
    <a href="https://www.laravel.com"><img alt="Laravel 12" src="https://img.shields.io/badge/Laravel-v12-orange?logo=Laravel&color=FF2D20"></a>
    <a href="https://www.laravel-livewire.com"><img alt="Laravel Livewire 2" src="https://img.shields.io/badge/Livewire-v3-orange?logo=livewire&color=4E56A6"></a>
    <a href="https://www.filamentphp.com"><img alt="Filament 3" src="https://img.shields.io/badge/Filament-v4-orange?logo=filament&color=4E56A6"></a>
</p>
<p align="center">
    <a href="https://app.codacy.com/gh/mooxphp/moox/dashboard"><img src="https://app.codacy.com/project/badge/Grade/2b912412bb6e4892b52688272dec1555" alt="Codacy Code Quality"></a>
    <a href="https://app.codacy.com/gh/mooxphp/moox/dashboard"><img src="https://app.codacy.com/project/badge/Coverage/2b912412bb6e4892b52688272dec1555" alt="Codacy Coverage"></a>
    <a href="https://snyk.io/test/github/mooxphp/moox"><img alt="Snyk Security" src="https://snyk.io/test/github/mooxphp/moox/badge.svg"></a>
    <a href="https://github.com/mooxphp/moox/issues/94"><img src="https://img.shields.io/badge/renovate-enabled-brightgreen.svg" alt="Renovate" /></a>
</p>
<p align="center">
    <a href="https://hosted.weblate.org/engage/moox/"><img src="https://hosted.weblate.org/widgets/moox/-/svg-badge.svg" alt="Translation status" /></a>
    <a href="https://allcontributors.org/"><img alt="All Contributors" src="https://img.shields.io/github/all-contributors/mooxphp/moox"></a>
    <a href="https://github.com/mooxphp/moox-app-components/blob/main/LICENSE.md"><img alt="License" src="https://img.shields.io/github/license/mooxphp/moox?color=blue&label=license"></a>
    <a href="https://mooxphp.slack.com/"><img alt="Slack" src="https://img.shields.io/badge/Slack-Moox-blue?logo=slack"></a>
    <br>
    <br>
</p>

# Moox

This is the Monorepo of the Moox Project. It is home of our ecosystem of Laravel packages and Filament plugins that are developed to form a CMS, Shop platform or other website or app.

If you want to install and use Moox, please refer to any of our packages or directly install a Bundle using Moox Core.

## Packages

| Package                                                                | Composer               | Free | Pro | State  |
| ---------------------------------------------------------------------- | ---------------------- | ---- | --- | ------ |
| [Moox Core](https://github.com/mooxphp/core)                           | moox/core              | x    |     | Stable |
| [Moox Jobs](https://github.com/mooxphp/jobs)                           | moox/jobs              | x    |     | Stable |
| [Moox Skeleton](https://github.com/mooxphp/skeleton)                   | moox/skeleton          | x    |     | Stable |
| [Moox Flag Icons Circle](https://github.com/mooxphp/flag-icons-circle) | moox/flag-icons-circle | x    |     | Stable |
| [Moox Flag Icons Origin](https://github.com/mooxphp/flag-icons-origin) | moox/flag-icons-origin | x    |     | Stable |
| [Moox Flag Icons Square](https://github.com/mooxphp/flag-icons-square) | moox/flag-icons-square | x    |     | Stable |
| [Moox Flag Icons Rect](https://github.com/mooxphp/flag-icons-rect)     | moox/flag-icons-rect   | x    |     | Stable |
| [Moox Laravel Icons](https://github.com/mooxphp/laravel-icons)         | moox/laravel-icons     | x    |     | Stable |
| [Moox Media](https://github.com/mooxphp/media)                         | moox/media             | x    |     | Beta   |
| [Moox Data](https://github.com/mooxphp/data)                           | moox/data              | x    |     | Beta   |
| [Moox Localization](https://github.com/mooxphp/localization)           | moox/localization      | x    |     | Beta   |
| [Moox Press](https://github.com/mooxphp/press)                         | moox/press             | x    |     | Beta   |

All others are under hard development.

## Requirements

| Moox Version | Laravel Version | Filament Version | PHP Version |
| ------------ | --------------- | ---------------- | ----------- |
| 2.x          | \> 9.x          | 2.x              | \> 8.0      |
| 3.x          | \> 10.x         | 3.x              | \> 8.1      |
| 4.x          | \> 11.x         | 4.x              | \> 8.2      |

Moox Press packages require WordPress Version 6.7, password hashing is currently not compatible with newer versions. We will fix that soon.

## Installation

Install and use the Monorepos ...

```bash
git clone https://github.com/mooxphp/moox
composer create-project laravel/laravel mooxdev
composer require moox/devlink
php artisan vendor:publish --tag="devlink-config"
php artisan moox:devlink
```

There is another option for running our CI ...

```bash
# Installs a fresh Laravel app and all packages
php ci.php
# or to have a special Laravel version running
php ci.php -l=11.0
# and to clean up the Laravel app
php ci.php -d
```

## Todo

### Monorepo

-   Get CI working again, parallel one action!
    1. Build Laravel app including database once
    2. Run Pest with Matrix for Win/X and supported PHP versions
    3. PHP Stan
    4. Codacy
-   Restore README.md
-   Release feature
-   Restore Art files
-   Restore Moox.org

### Filament 4

-   Copy over every Fila4-ready package
-   Restore the composer.json
-   Restore changes from Moox Jobs
-   Care for News, Monorepo, Github ...

### Installer

-   Check and Install Filament incl. user
-   Create Panels, suggest Bundles
-   Install and use Package Registry

## License

This repository and all packages are commercial software under the [MIT License](./LICENSE.md).

## Security

Before reporting a security issues, please read our [Security Policy](./SECURITY.md).
