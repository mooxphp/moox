# TallUI :package_description

[![Latest Version on Packagist](https://img.shields.io/packagist/v/:vendor_slug/:package_slug.svg?style=flat-square)](https://packagist.org/packages/:vendor_slug/:package_slug)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/:vendor_slug/:package_slug/run-tests?label=tests)](https://github.com/:vendor_slug/:package_slug/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/:vendor_slug/:package_slug/Check%20&%20fix%20styling?label=code%20style)](https://github.com/:vendor_slug/:package_slug/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/:vendor_slug/:package_slug.svg?style=flat-square)](https://packagist.org/packages/:vendor_slug/:package_slug)

<!--delete-->
---
This repo can be used to scaffold a TallUI package. Follow these steps to get started:

1. Press the "Use this template" button at the top of this repo to create a new repo with the contents of this skeleton.
2. Run "php ./configure.php" to run a script that will replace all placeholders throughout all the files.
3. Have fun developing your package.

This package is based on [Laravel Package Tools by Spatie](https://github.com/spatie/laravel-package-tools), learn more about the simplified scaffolding of your service-provider there.

If you need help creating a package, consider picking up the <a href="https://laravelpackage.training">Laravel Package Training by Spatie</a> video course.

For most of the questions in configure.php, there will be an example migration, command, module, widget, block and component configured. You can directly start to build your package editing these examples. If you need more than one migration, command, module, widget, block or component, you can add these as an array. Don't forget to switch between

- ```hasMigration``` and ```hasMigrations```, or call ```hasMigration``` multiple times
- ```hasCommand``` and ```hasCommands```, or call ```hasCommand``` multiple times
- ```hasModule``` and ```hasModules```, or call ```hasModule``` multiple times
- ```hasWidget``` and ```hasWidgets```, or call ```hasWidget``` multiple times
- ```hasBlock``` and ```hasBlocks```, or call ```hasBlock``` multiple times

We stick to the conventions made in [Spatie's Laravel Package Tools](https://github.com/spatie/laravel-package-tools). If you are unsure about the syntax, read there.

## Todo:

Think about how to add each of these questions, or make them default.

### Questions in configure.php

- Does the package include config? Y/n
- Does the package include views? Y/n
- Does the package include translations? Y/n
- Does the package include migrations? Y/n
- Does the package include commands? Y/n
- Does the package include admin modules? Y/n
- Does the package include admin widgets? Y/n
- Does the package include editor blocks? Y/n
- Does the package include an admin theme? Y/n
- Does the package include a website theme? Y/n
- Does the package include docs? Y/n
- Does the package include blade components? Y/n
- Does the package include livewire components? Y/n

### Service Provider

- Add the package-provider in core or packages, extend spatie and use the own package provider
- Add a demo blade and livewire-component
- Add languages? Which ones?
- Scaffold! :-)

### Config

- Idea: use config from blade-ui-kit instead of service provider
- Design: add theming

### Docs

Probably a good idea to:

- How to create ... modules, ...

---
<!--/delete-->
This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require :vendor_slug/:package_slug
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag=":package_slug-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag=":package_slug-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag=":package_slug-views"
```

## Usage

```php
$variable = new VendorName\Skeleton();
echo $variable->echoPhrase('Hello, VendorName!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

This package is based on Package Skeleton Laravel from [Spatie](https://spatie.be/products). If you are a Laravel developer, their services, products and trainings are for you. Otherwise they love post cards.

- [:author_name](https://github.com/:author_username)
- [TALLUI Devs](https://github.com/orgs/usetall/people)
- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
