# TallUI Form Components

[![Latest Version on Packagist](https://img.shields.io/packagist/v/usetall/tallui-form-components.svg?style=flat-square)](https://packagist.org/packages/usetall/tallui-form-components)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/usetall/tallui-form-components/run-tests?label=tests)](https://github.com/usetall/tallui-form-components/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/usetall/tallui-form-components/Fix%20PHP%20code%20style%20issues?label=code%20style)](https://github.com/usetall/tallui-form-components/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/usetall/tallui-form-components.svg?style=flat-square)](https://packagist.org/packages/usetall/tallui-form-components)


This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

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

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="tallui-form-components-views"
```

## Usage

```php
$talluiFormComponents = new Usetall\TalluiFormComponents();
echo $talluiFormComponents->echoPhrase('Hello, Usetall!');
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

This package is based on Package TalluiFormComponents Laravel from [Spatie](https://spatie.be/products). If you are a Laravel developer, their services, products and trainings are for you. Otherwise they love post cards.

- [TallUI Developers](https://github.com/usetall)
- [TALLUI Devs](https://github.com/orgs/usetall/people)
- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
