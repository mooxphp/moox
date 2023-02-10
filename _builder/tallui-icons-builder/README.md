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

This repo can be used to scaffold a TallUI icon packages. Follow these steps to get started:

Press the "Use this template" button at the top of this repo to create a new repo with the contents of this tallui-package-builder.
Run "php ./configure.php" to run a script that will replace all placeholders throughout all the files.
Have fun developing your package.

A package to easily make use of [TallUIIconsBuilder](https://github.com/refactoringui/icons) in your Laravel Blade views.

For a full list of available icons see [the SVG directory](resources/svg) or preview them at [icons.com](https://icons.com/).

### Questions in configure.php

-   Does the package include config? Y/n
-   Does the package include views? Y/n
-   Does the package include translations? Y/n
-   Does the package include migrations? Y/n
-   Does the package include commands? Y/n
-   Does the package include admin modules? Y/n
-   Does the package include admin widgets? Y/n
-   Does the package include editor blocks? Y/n
-   Does the package include an admin theme? Y/n
-   Does the package include a website theme? Y/n
-   Does the package include docs? Y/n
-   Does the package include blade components? Y/n
-   Does the package include livewire components? Y/n

## Requirements

-   PHP 8.1 or higher
-   Laravel 9.0 or higher

## Blade Icons

TallUIIconsBuilder uses Blade Icons under the hood. Please refer to [the Blade Icons readme](https://github.com/blade-ui-kit/blade-icons) for additional functionality. We also recommend to [enable icon caching](https://github.com/blade-ui-kit/blade-icons#caching) with this library.

## Configuration

TallUIIconsBuilder also offers the ability to use features from Blade Icons like default classes, default attributes, etc. If you'd like to configure these, publish the `tallui-icons-builder.php` config file:

```bash
php artisan vendor:publish --tag=tallui-icons-builder-config
```

## Usage

Copy all icons in /ressource folder and you are ready to go.

Icons can be used as self-closing Blade components which will be compiled to SVG icons:

```blade
<x-webicons-adjustments/>
```

You can also pass classes to your icon components:

```blade
<x-webicons-adjustments class="w-6 h-6 text-gray-500"/>
```

And even use inline styles:

```blade
<x-webicons-adjustments style="color: #555"/>
```

The solid icons can be referenced like this:

```blade
<x-webicons-adjustments/>
```

### Raw SVG Icons

If you want to use the raw SVG icons as assets, you can publish them using:

```bash
php artisan vendor:publish --tag=tallui-icons-builder --force
```

Then use them in your views like:

```blade
<img src="{{ asset('vendor/tallui-icons-builder/adjustments.svg') }}" width="10" height="10"/>
```

## Changelog

Check out the [CHANGELOG](CHANGELOG.md) in this repository for all the recent changes.

## Maintainers

TallUIIconsBuilder is developed and maintained by TallUI Devs.

## License

TallUIIconsBuilder is open-sourced software licensed under [the MIT license](LICENSE.md).
