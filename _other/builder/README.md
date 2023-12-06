<p align="center">
    <br>
  	<img src="https://github.com/mooxphp/moox/raw/main/_other/art/moox-logo.png" width="200" alt="Moox Logo">
    <br>
</p><br>

<p align="center">
    <a href="https://github.com/mooxphp/moox/actions/workflows/pest.yml">
        <img alt="PEST Tests" src="https://github.com/mooxphp/moox/actions/workflows/pest.yml/badge.svg">
    </a>
    <a href="https://github.com/mooxphp/moox/actions/workflows/pint.yml">
        <img alt="Laravel PINT PHP Code Style" src="https://github.com/mooxphp/moox/actions/workflows/pint.yml/badge.svg">
    </a>
    <a href="https://github.com/mooxphp/moox/actions/workflows/phpstan.yml">
        <img alt="PHPStan Level 5" src="https://github.com/mooxphp/moox/actions/workflows/phpstan.yml/badge.svg">
    </a>
</p>
<p align="center">
    <a href="https://www.tailwindcss.com">
        <img alt="TailwindCSS 3" src="https://img.shields.io/badge/TailwindCSS-v3-orange?logo=tailwindcss&color=06B6D4">
    </a>
    <a href="https://www.alpinejs.dev">
        <img alt="AlpineJS 3" src="https://img.shields.io/badge/AlpineJS-v3-orange?logo=alpine.js&color=8BC0D0">
    </a>
    <a href="https://www.laravel.com">
        <img alt="Laravel 10" src="https://img.shields.io/badge/Laravel-v10-orange?logo=Laravel&color=FF2D20">
    </a>
    <a href="https://www.laravel-livewire.com">
        <img alt="Laravel Livewire 2" src="https://img.shields.io/badge/Livewire-v2-orange?logo=livewire&color=4E56A6">
    </a>
</p>
<p align="center">
    <a href="https://app.codacy.com/gh/mooxphp/moox/dashboard">
        <img src="https://app.codacy.com/project/badge/Grade/2b912412bb6e4892b52688272dec1555" alt="Codacy Code Quality">
    </a>
    <a href="https://app.codacy.com/gh/mooxphp/moox/dashboard">
        <img src="https://app.codacy.com/project/badge/Coverage/2b912412bb6e4892b52688272dec1555" alt="Codacy Coverage">
    </a>
    <a href="https://codeclimate.com/github/mooxphp/moox/maintainability">
        <img src="https://api.codeclimate.com/v1/badges/1b6dae4442e751fd60b9/maintainability" alt="Code Climate Maintainability">
    </a>
    <a href="https://snyk.io/test/github/mooxphp/moox">
        <img alt="Snyk Security" src="https://snyk.io/test/github/mooxphp/moox/badge.svg">
    </a>
</p>
<p align="center">
    <a href="https://github.com/mooxphp/moox/issues/94">
        <img src="https://img.shields.io/badge/renovate-enabled-brightgreen.svg" alt="Renovate" />
    </a>
    <a href="https://hosted.weblate.org/engage/tallui/">
        <img src="https://hosted.weblate.org/widgets/tallui/-/svg-badge.svg" alt="Translation status" />
    </a>
    <a href="https://github.com/mooxphp/moox-app-components/blob/main/LICENSE.md">
        <img alt="License" src="https://img.shields.io/github/license/mooxphp/moox?color=blue&label=license">
    </a>
    <a href="https://mooxphp.slack.com/">
        <img alt="Slack" src="https://img.shields.io/badge/Slack-Moox-blue?logo=slack">
    </a>
    <br>
    <br>
</p>

# Moox Builder

This template is used for generating all Moox packages. Press the Template-Button in GitHub, to create your own.

Learn more: [Builder Docs](https://moox.org/builder)

If you install it, it will completely work without beeing useful. Guaranteed!

## Installation

Install the package via composer:

```bash
composer require moox/builder
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="builder-migrations"
php artisan migrate
```

Publish the config file with:

```bash
php artisan vendor:publish --tag="builder-config"
```

### Or 
```bash
php artisan `moox:install`
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
