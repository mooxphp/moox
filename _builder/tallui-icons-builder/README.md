# TallUIIconsBuilder

<a href="https://github.com/usetall/tallui-icons-builder/actions?query=workflow%3ATests">
    <img src="https://github.com/blade-ui-kit/tallui-icons-builder/workflows/Tests/badge.svg" alt="Tests">
</a>
<a href="https://packagist.org/packages/usetall/tallui-icons-builder">
    <img src="https://img.shields.io/packagist/v/usetall/tallui-icons-builder" alt="Latest Stable Version">
</a>
<a href="https://packagist.org/packages/usetall/tallui-icons-builder">
    <img src="https://img.shields.io/packagist/dt/usetall/tallui-icons-builder" alt="Total Downloads">
</a>

> This is a template repository for new icon packages for [Blade Icons](https://github.com/blade-ui-kit/blade-icons). Start a new repo with this and replace the relevant things below:
>
> 1. `usetall` with your GitHub organization
> 2. `tallui-icons-builder` with your repository name
> 3. `TallUIIconsBuilder` & `TallUIIconsBuilder` with your icon set name
> 4. Any other reference to `TallUIIconsBuilder` with your icon set name
> 5. `TallUI Devs` with your name
>
> Then, make sure [the implementation](./src) is correct, that you set up [icon generation](https://github.com/blade-ui-kit/blade-icons#generating-icons) and that [your tests](./tests) pass. And remove this quote block from your readme. When you've published your package on Packagist, make sure to send it in to [the Blade Icons package list](https://github.com/blade-ui-kit/blade-icons#icon-packages).

A package to easily make use of [TallUIIconsBuilder](https://github.com/refactoringui/icons) in your Laravel Blade views.

For a full list of available icons see [the SVG directory](resources/svg) or preview them at [icons.com](https://icons.com/).

## Requirements

- PHP 8.1 or higher
- Laravel 9.0 or higher

## Installation

```bash
composer require usetall/tallui-icons-builder
```

## Updating

Please refer to [`the upgrade guide`](UPGRADE.md) when updating the library.

## Blade Icons

TallUIIconsBuilder uses Blade Icons under the hood. Please refer to [the Blade Icons readme](https://github.com/blade-ui-kit/blade-icons) for additional functionality. We also recommend to [enable icon caching](https://github.com/blade-ui-kit/blade-icons#caching) with this library.

## Configuration

TallUIIconsBuilder also offers the ability to use features from Blade Icons like default classes, default attributes, etc. If you'd like to configure these, publish the `tallui-icons-builder.php` config file:

```bash
php artisan vendor:publish --tag=tallui-icons-builder-config
```

## Usage

Icons can be used as self-closing Blade components which will be compiled to SVG icons:

```blade
<x-icon-o-adjustments/>
```

You can also pass classes to your icon components:

```blade
<x-icon-o-adjustments class="w-6 h-6 text-gray-500"/>
```

And even use inline styles:

```blade
<x-icon-o-adjustments style="color: #555"/>
```

The solid icons can be referenced like this:

```blade
<x-icon-s-adjustments/>
```

### Raw SVG Icons

If you want to use the raw SVG icons as assets, you can publish them using:

```bash
php artisan vendor:publish --tag=tallui-icons-builder --force
```

Then use them in your views like:

```blade
<img src="{{ asset('vendor/tallui-icons-builder/o-adjustments.svg') }}" width="10" height="10"/>
```

## Changelog

Check out the [CHANGELOG](CHANGELOG.md) in this repository for all the recent changes.

## Maintainers

TallUIIconsBuilder is developed and maintained by TallUI Devs.

## License

TallUIIconsBuilder is open-sourced software licensed under [the MIT license](LICENSE.md).
