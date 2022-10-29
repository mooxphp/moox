<p align="center">
    <img src="https://github.com/usetall/tallui/raw/main/_others/tallui-art/tallui-logo.svg" width="100" alt="TallUI Logo">
    <br><br>
    <img src="https://github.com/usetall/tallui/raw/main/_others/tallui-art/tallui-textlogo.svg" width="110" alt="TallUI Textlogo">
</p><br><br>

<p align="center">
    <a href="https://packagist.org/packages/usetall/tallui-app-components">
    	<img alt="Latest Version on Packagist" src="https://img.shields.io/packagist/v/usetall/tallui-app-components.svg?color=blue&label=Packagist&style=flat-square">
    </a>
    <a href="https://packagist.org/packages/usetall/tallui-app-components">
    	<img alt="Total Downloads" src="https://img.shields.io/packagist/dt/usetall/tallui-app-components.svg?color=blue&label=Downloads&style=flat-square">
    </a>
</p>
<p align="center">
    <a href="https://github.com/usetall/tallui-app-components/actions/workflows/run-tests.yml">
        <img alt="PEST Tests" src="https://img.shields.io/github/workflow/status/usetall/tallui-app-components/run-tests?color=darkgreen&label=Pest&style=flat-square">
    </a>
    <a href="https://github.com/usetall/tallui-app-components/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain">
        <img alt="Laravel PINT PHP Code Style" src="https://img.shields.io/github/workflow/status/usetall/tallui-app-components/Fix%20PHP%20code%20style%20issues?color=darkgreen&label=Laravel Pint&style=flat-square">
    </a>
    <a href="https://github.com/usetall/tallui-app-components/actions?query=workflow%3A"PHPStan"+branch%3Amain">
        <img alt="PHPStan Level 5" src="https://img.shields.io/github/workflow/status/usetall/tallui-app-components/PHPStan?color=darkgreen&label=PHPStan&style=flat-square">
    </a>
    <a href="https://scrutinizer-ci.com/g/usetall/tallui/?branch=main">
        <img alt="Scrutinizer Code Quality" src="https://img.shields.io/scrutinizer/quality/g/usetall/tallui/main?color=darkgreen&label=Code%20Quality&style=flat-square">
    </a>
    <a href="https://scrutinizer-ci.com/g/usetall/tallui/?branch=main">
        <img alt="License" src="https://img.shields.io/scrutinizer/coverage/g/usetall/tallui/main?color=darkgreen&label=Coverage&style=flat-square">
    </a>
</p>
<p align="center">
    <a href="https://github.com/usetall/tallui-app-components/blob/main/LICENSE.md">
        <img alt="License" src="https://img.shields.io/github/license/usetall/tallui-app-components?color=blue&label=License&style=flat-square">
    </a>
    <a href="https://github.com/sponsors/adrolli">
        <img alt="Sponsoring" src="https://img.shields.io/github/sponsors/adrolli?label=Sponsors&style=flat-square">
    </a>
    
<br><br></p>

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

## Testing

You can run all tests in Pest:

```bash
composer test
```

or including test coverage:

```bash
composer test-coverage
```

as well as Laravel Pint (aka PHP CS Fixer):

```bash
composer format
```

and last but not least PHPStan:

```bash
composer analyse
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

For all informations about development and contributions head over to the [TallUI Monorepo](https://github.com/usetall/tallui).

## Security Vulnerabilities

Please report all security related issues to security@tallui.io.

## Credits

This package is based on Package TalluiWebComponents Laravel from [Spatie](https://spatie.be/products). If you are a Laravel developer, their services, products and trainings are for you. Otherwise they love post cards.

- [TallUI Developers](https://github.com/usetall)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.


## Contributors

<!-- readme: adrolli,Reinhold-Jesse,collaborators,contributors,tallui-bot,bots -start -->
<!-- readme: adrolli,Reinhold-Jesse,collaborators,contributors,tallui-bot,bots -end -->
 