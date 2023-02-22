<p align="center">
    <img src="https://github.com/usetall/tallui/raw/main/_others/tallui-art/tallui-logo.svg" width="100" alt="TallUI Logo">
    <br><br>
    <img src="https://github.com/usetall/tallui/raw/main//_others/tallui-art/tallui-textlogo.svg" width="110" alt="TallUI Textlogo">
</p><br>



<p align="center">
    <a href="https://github.com/usetall/tallui/actions/workflows/pest.yml">
        <img alt="PEST Tests" src="https://github.com/usetall/tallui/actions/workflows/pest.yml/badge.svg">
    </a>
    <a href="https://github.com/usetall/tallui/actions/workflows/pint.yml">
        <img alt="Laravel PINT PHP Code Style" src="https://github.com/usetall/tallui/actions/workflows/pint.yml/badge.svg">
    </a>
    <a href="https://github.com/usetall/tallui/actions/workflows/phpstan.yml">
        <img alt="PHPStan Level 5" src="https://github.com/usetall/tallui/actions/workflows/phpstan.yml/badge.svg">
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
    <a href="https://app.codacy.com/gh/usetall/tallui/dashboard">
        <img src="https://app.codacy.com/project/badge/Grade/2b912412bb6e4892b52688272dec1555" alt="Codacy Code Quality">
    </a>
    <a href="https://app.codacy.com/gh/usetall/tallui/dashboard">
        <img src="https://app.codacy.com/project/badge/Coverage/2b912412bb6e4892b52688272dec1555" alt="Codacy Coverage">
    </a>
    <a href="https://codeclimate.com/github/usetall/tallui/maintainability">
        <img src="https://api.codeclimate.com/v1/badges/1b6dae4442e751fd60b9/maintainability" alt="Code Climate Maintainability">
    </a>
    <a href="https://app.snyk.io/org/adrolli/project/dd7d7d2c-7a0c-4741-ab01-e3d11ea18fa0">
        <img alt="Snyk Security" src="https://img.shields.io/snyk/vulnerabilities/github/usetall/tallui">
    </a>
</p>
<p align="center">
    <a href="https://github.com/usetall/tallui/issues/94">
        <img src="https://img.shields.io/badge/renovate-enabled-brightgreen.svg" alt="Renovate" />
    </a>
    <a href="https://hosted.weblate.org/engage/tallui/">
        <img src="https://hosted.weblate.org/widgets/tallui/-/svg-badge.svg" alt="Translation status" />
    </a>
    <a href="https://github.com/usetall/tallui-app-components/blob/main/LICENSE.md">
        <img alt="License" src="https://img.shields.io/github/license/usetall/tallui-app-components?color=blue&label=license">
    </a>
    <a href="https://tallui.slack.com/">
        <img alt="Slack" src="https://img.shields.io/badge/Slack-TallUI-blue?logo=slack">
    </a>
    <br>
    <br>
</p>


# TallUI Form Components

Welcome to the TallUI project. We are in an early stage of development. We will soon publish our first components and packages for Laravel and the TALL-Stack. Stay tuned.

TallUI Form Components is a collection of Blade and Livewire components for TallUI or any Laravel project using the [TALL-Stack](https://tallstack.dev).

## Requirements

TallUI Components are dependency-free. You can use all of our components without using any other TallUI package. You just need to include TailwindCSS, AlpineJS and Laravel Livewire. Feel free to develop whatever Laravel package you want.

-   [PHP 8.2](https://www.php.net/)
-   [Laravel 10](https://laravel.com/)
-   [Laravel Livewire 2](https://laravel-livewire.com/)
-   [TailwindCSS v3](https://tailwindcss.com/)
-   [Alpine.js v3](https://alpinejs.dev/)

If you want to smart-start with TallUI, require [TallUI Core](https://github.com/usetall/tallui-core) or [TallUI AdminPanel](https://github.com/usetall/tallui-admin-panel). They do the heavy lifting for you.

Another good starting point to have the TALL-Stack up and running right away is [Laravel Jetstream](https://jetstream.laravel.com/):

```bash
composer require laravel/jetstream
php artisan jetstream:install livewire
php artisan migrate
npm install
```

## Installation

You can install the package via composer:

```bash
composer require usetall/tallui-form-components
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="tallui-form-components-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="tallui-form-components-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="tallui-form-components-views"
```

## Testing

You can run all tests in Pest:

```bash
composer test
```

or including test coverage:

```bash
composer test-coverage
```

do auto-formatting with Laravel Pint (aka PHP CS Fixer):

```bash
composer format
```

and last but not least use PHPStan, the best static analyzer for PHP:

```bash
composer analyse
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/usetall/tallui/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](https://github.com/usetall/tallui/security/policy) on how to report security vulnerabilities.

## Credits

This package is based on Package Skeleton Laravel from [Spatie](https://spatie.be/products). If you are a Laravel developer, their services, products and trainings are for you. Otherwise they love post cards.

-   [TALLUI Devs](https://github.com/orgs/usetall/people)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
