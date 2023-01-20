# TallUI Icons Builder

<a href="https://github.com/tallui-organization/tallui-heroicons/actions?query=workflow%3ATests">
    <img src="https://github.com/tallui-ui-kit/tallui-heroicons/workflows/Tests/badge.svg" alt="Tests">
</a>
<a href="https://packagist.org/packages/tallui-organization/tallui-heroicons">
    <img src="https://img.shields.io/packagist/v/tallui-organization/tallui-heroicons" alt="Latest Stable Version">
</a>
<a href="https://packagist.org/packages/tallui-organization/tallui-heroicons">
    <img src="https://img.shields.io/packagist/dt/tallui-organization/tallui-heroicons" alt="Total Downloads">
</a>

> This is a template repository for new icon packages for [Tallui Icons](https://github.com/tallui-ui-kit/tallui-icons). Start a new repo with this and replace the relevant things below:
>
> 1. `tallui-organization` with your GitHub organization
> 2. `tallui-heroicons` with your repository name
> 3. `Tallui Heroicons` & `Tallui Icons Template` with your icon set name
> 4. Any other reference to `Heroicons` with your icon set name
> 5. `Tallui Developer` with your name
>
> Then, make sure [the implementation](./src) is correct, that you set up [icon generation](https://github.com/tallui-ui-kit/tallui-icons#generating-icons) and that [your tests](./tests) pass. And remove this quote block from your readme. When you've published your package on Packagist, make sure to send it in to [the Tallui Icons package list](https://github.com/tallui-ui-kit/tallui-icons#icon-packages).

A package to easily make use of [Heroicons](https://github.com/refactoringui/heroicons) in your Laravel Tallui views.

For a full list of available icons see [the SVG directory](resources/svg) or preview them at [heroicons.com](https://heroicons.com/).

## Requirements

-   PHP 8.1 or higher
-   Laravel 9.0 or higher

## Installation

```bash
composer require tallui-organization/tallui-heroicons
```

## Updating

Please refer to [`the upgrade guide`](UPGRADE.md) when updating the library.

## Tallui Icons

Tallui Heroicons uses Tallui Icons under the hood. Please refer to [the Tallui Icons readme](https://github.com/tallui-ui-kit/tallui-icons) for additional functionality. We also recommend to [enable icon caching](https://github.com/tallui-ui-kit/tallui-icons#caching) with this library.

## Configuration

Tallui Heroicons also offers the ability to use features from Tallui Icons like default classes, default attributes, etc. If you'd like to configure these, publish the `tallui-heroicons.php` config file:

```bash
php artisan vendor:publish --tag=tallui-heroicons-config
```

## Usage

Icons can be used as self-closing Tallui components which will be compiled to SVG icons:

```tallui
<x-heroicon-o-adjustments/>
```

You can also pass classes to your icon components:

```tallui
<x-heroicon-o-adjustments class="w-6 h-6 text-gray-500"/>
```

And even use inline styles:

```tallui
<x-heroicon-o-adjustments style="color: #555"/>
```

The solid icons can be referenced like this:

```tallui
<x-heroicon-s-adjustments/>
```

### Raw SVG Icons

If you want to use the raw SVG icons as assets, you can publish them using:

```bash
php artisan vendor:publish --tag=tallui-heroicons --force
```

Then use them in your views like:

```tallui
<img src="{{ asset('vendor/tallui-heroicons/o-adjustments.svg') }}" width="10" height="10"/>
```

## Changelog

Check out the [CHANGELOG](CHANGELOG.md) in this repository for all the recent changes.

## Maintainers

Tallui Heroicons is developed and maintained by Tallui Developer.

## License

Tallui Heroicons is open-sourced software licensed under [the MIT license](LICENSE.md).
