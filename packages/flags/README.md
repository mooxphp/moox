# Moox Flags

<a href="https://github.com/mooxphp/flags/actions?query=workflow%3ATests">
    <img src="https://github.com/blade-ui-kit/flags/workflows/Tests/badge.svg" alt="Tests">
</a>
<a href="https://packagist.org/packages/moox/flags">
    <img src="https://img.shields.io/packagist/v/moox/flags" alt="Latest Stable Version">
</a>
<a href="https://packagist.org/packages/moox/flags">
    <img src="https://img.shields.io/packagist/dt/moox/flags" alt="Total Downloads">
</a>

> 5. `Blade Developer` with your name
>
> Then, make sure [the implementation](./src) is correct, that you set up [icon generation](https://github.com/blade-ui-kit/blade-icons#generating-icons) and that [your tests](./tests) pass. And remove this quote block from your readme. When you've published your package on Packagist, make sure to send it in to [the Blade Icons package list](https://github.com/blade-ui-kit/blade-icons#icon-packages).

A package to easily make use of beautiful stylable country and language flags in your Laravel Blade views.

For a full list of available icons see [the SVG directory](resources/svg).

## Requirements

-   PHP 8.2 or higher
-   Laravel 10.0 or higher

## Installation

```bash
composer require moox/flags
```

## Updating

Please refer to [`the upgrade guide`](UPGRADE.md) when updating the library.

## Blade Icons

Moox Flags uses Blade Icons under the hood. Please refer to [the Blade Icons readme](https://github.com/blade-ui-kit/blade-icons) for additional functionality. We also recommend to [enable icon caching](https://github.com/blade-ui-kit/blade-icons#caching) with this library.

## Configuration

Moox Flags also offers the ability to use features from Blade Icons like default classes, default attributes, etc. If you'd like to configure these, publish the `flags.php` config file:

```bash
php artisan vendor:publish --tag=flags-config
```

## Usage

Icons can be used as self-closing Blade components which will be compiled to SVG icons:

```blade
<x-heroicon-o-adjustments/>
```

You can also pass classes to your icon components:

```blade
<x-heroicon-o-adjustments class="w-6 h-6 text-gray-500"/>
```

And even use inline styles:

```blade
<x-heroicon-o-adjustments style="color: #555"/>
```

The solid icons can be referenced like this:

```blade
<x-heroicon-s-adjustments/>
```

### Raw SVG Icons

If you want to use the raw SVG icons as assets, you can publish them using:

```bash
php artisan vendor:publish --tag=flags --force
```

Then use them in your views like:

```blade
<img src="{{ asset('vendor/flags/o-adjustments.svg') }}" width="10" height="10"/>
```

## Changelog

Check out the [CHANGELOG](CHANGELOG.md) in this repository for all the recent changes.

## Maintainers

Moox Flags is developed and maintained by Blade Developer.

## License

Moox Flags is open-sourced software licensed under [the MIT license](LICENSE.md).
