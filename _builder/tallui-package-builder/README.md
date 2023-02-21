<p align="center">
    <a href="https://tallui.io" target="_blank"><img src="https://github.com/usetall/tallui/raw/main/_others/tallui-art/tallui-logo.svg" width="100" alt="TallUI Logo"></a>
        <br><br>
      <a href="https://tallui.io" target="_blank">
        <img src="https://github.com/usetall/tallui/raw/main//_others/tallui-art/tallui-textlogo.svg" width="110" alt="TallUI Textlogo">
    </a>
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

# TallUI Package Builder

<!--delete-->

---

This repo can be used to scaffold a TallUI package. Follow these steps to get started:

1. Press the "Use this template" button at the top of [this repo](https://github.com/usetall/tallui-package-builder/) to create a new repo with the contents of this tallui-package-builder.
2. Run "php ./configure.php" to run a script that will replace all placeholders throughout all the files.
3. Have fun developing your package.

This package is based on [Laravel Package Tools by Spatie](https://github.com/spatie/laravel-package-tools), learn more about the simplified scaffolding of your service-provider there.

If you need help creating a package, consider picking up the <a href="https://laravelpackage.training">Laravel Package Training by Spatie</a> video course.

For most of the questions in configure.php, there will be an example migration, command, module, widget, block and component configured. You can directly start to build your package editing these examples. If you need more than one migration, command, module, widget, block or component, you can add these as an array. Don't forget to switch between

-   `hasMigration` and `hasMigrations`, or call `hasMigration` multiple times
-   `hasCommand` and `hasCommands`, or call `hasCommand` multiple times
-   `hasModule` and `hasModules`, or call `hasModule` multiple times
-   `hasWidget` and `hasWidgets`, or call `hasWidget` multiple times
-   `hasBlock` and `hasBlocks`, or call `hasBlock` multiple times

And there are two demo components, you can call from any blade-file

-   `<x-package-builder-blade-component />`
-   `<livewire:package-builder-livewire-component />` or `@livewire('package-builder-livewire-component')`

They are wired in the config-file of the package. To start developing components you may rename or copy these components and (re)-wire them in your config file.

We stick to the conventions made in [Spatie's Laravel Package Tools](https://github.com/spatie/laravel-package-tools). If you are unsure about the syntax, read there.

---

<!--/delete-->

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require tallui_package_builder/tallui-package-builder
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="tallui-package-builder-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="tallui-package-builder-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="tallui-package-builder-views"
```

## Usage

```php
$variable = new Usetall\TalluiPackageBuilder();
echo $variable->echoPhrase('Hello, Usetall!');
```

## Components

There are two components, you can call from any blade-file

-   `<x-package-builder-blade-component />`
-   `<livewire:package-builder-livewire-component />` or `@livewire('package-builder-livewire-component')`

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

This package is based on Package Skeleton Laravel from [Spatie](https://spatie.be/products). If you are a Laravel developer, their services, products and trainings are for you. Otherwise they love post cards.

-   [TALLUI Devs](https://github.com/orgs/usetall/people)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
