<p align="center">
    <img src="../../_others/tallui-art/tallui-logo.svg" width="100" alt="TallUI Logo">
    <br><br>
    <img src="../../_others/tallui-art/tallui-textlogo.svg" width="110" alt="TallUI Textlogo">
</p>


<br>

<p align="center">
    <a href="https://packagist.org/packages/usetall/tallui-app-components">
    	<img alt="Latest Version on Packagist" src="https://img.shields.io/packagist/v/usetall/tallui-app-components.svg?style=flat">
    </a>
    <a href="https://packagist.org/packages/usetall/tallui-app-components">
    	<img alt="Total Downloads" src="https://img.shields.io/packagist/dt/usetall/tallui-app-components.svg?style=flat">
    </a>
    <a href="https://github.com/usetall/tallui-app-components/actions/workflows/run-tests.yml">
        <img alt="PEST Tests" src="https://img.shields.io/github/workflow/status/usetall/tallui-app-components/run-tests?label=PestPHP">
    </a>
    <a href="https://github.com/usetall/tallui-app-components/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain">
        <img alt="Laravel PINT PHP Code Style" src="https://img.shields.io/github/workflow/status/usetall/tallui-app-components/Fix%20PHP%20code%20style%20issues?label=Laravel Pint">
    </a>
    <a href="https://github.com/usetall/tallui-app-components/actions?query=workflow%3A"PHPStan"+branch%3Amain">
        <img alt="PHPStan Level 5" src="https://img.shields.io/github/workflow/status/usetall/tallui-app-components/PHPStan?label=PHPStan">
    </a>
    <a href="https://scrutinizer-ci.com/g/usetall/tallui/?branch=main">
        <img alt="Scrutinizer Code Quality" src="https://scrutinizer-ci.com/g/usetall/tallui/badges/quality-score.png?b=main">
    </a>
    <a href="https://github.com/usetall/tallui-app-components/blob/main/LICENSE.md">
        <img alt="License" src="https://img.shields.io/github/license/usetall/tallui-app-components">
    </a>
</p>

# TallUI App Components

TallUI App Components is a collection of Blade and Livewire components for TallUI. You can use all of our components without further requirements for development of your own Laravel app or package.

## Components

Work-in-progress. There are no components yet. Some of these will become components soon:

- https://www.youtube.com/watch?v=t6S5DAo6Elo
- https://laravel-livewire.com/screencasts/s7-intro
- https://livewire-datatables.com/complex
- https://tables.laravel-boilerplate.com/tailwind
- https://github.com/mediconesystems/livewire-datatables
- https://livewire-powergrid.com/#/
- https://github.com/rappasoft/laravel-livewire-tables
- https://datatables.net/
- https://github.com/tanthammar/tall-forms
- https://laravelviews.com/

## Requirements

- [PHP 8.1](https://www.php.net/)
- [Laravel 9](https://laravel.com/)
- [Laravel Livewire 2](https://laravel-livewire.com/)
- [TailwindCSS v3](https://tailwindcss.com/)
- [Alpine.js v3](https://alpinejs.dev/)

A really good starting point to have the TALL-Stack up and running right away is [Laravel Jetstream](https://jetstream.laravel.com/):

```bash
composer require laravel/jetstream
php artisan jetstream:install livewire
npm install
npm run build
php artisan migrate
```

## Installation

Install the package via composer:

```bash
composer require usetall/tallui-app-components
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="tallui-app-components-config"
```

Feel free to disable single components or change the version, CDN or local path for assets loaded by a component.

Optionally, you can publish the components using

```bash
php artisan vendor:publish --tag="tallui-web-components-views"
```











Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="tallui-web-components-views"
```

## Usage

```php
$talluiWebComponents = new Usetall\TalluiWebComponents();
echo $talluiWebComponents->echoPhrase('Hello, Usetall!');
```

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

This package is based on Package TalluiWebComponents Laravel from [Spatie](https://spatie.be/products). If you are a Laravel developer, their services, products and trainings are for you. Otherwise they love post cards.

- [TallUI Developers](https://github.com/usetall)
- [TALLUI Devs](https://github.com/orgs/usetall/people)
- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.