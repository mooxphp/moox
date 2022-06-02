# TallUI This is my package tallui-web-components

[![Latest Version on Packagist](https://img.shields.io/packagist/v/usetall/tallui-web-components.svg?style=flat-square)](https://packagist.org/packages/usetall/tallui-web-components)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/usetall/tallui-web-components/run-tests?label=tests)](https://github.com/usetall/tallui-web-components/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/usetall/tallui-web-components/Check%20&%20fix%20styling?label=code%20style)](https://github.com/usetall/tallui-web-components/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/usetall/tallui-web-components.svg?style=flat-square)](https://packagist.org/packages/usetall/tallui-web-components)


This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require usetall/tallui-web-components
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="tallui-web-components-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="tallui-web-components-config"
```

This is the contents of the published config file:

```php
return [
];
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

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

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
