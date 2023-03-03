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

# tallui-flags-origin



This is where your description should go. Link to the original icons package, if the icons are not drawn by yourself.

## Requirements

-   PHP 8.2 or higher
-   Laravel 10.0 or higher

## Installation

You can install the package via composer:

```bash
composer require usetall/tallui-flags-origin
```

## Blade Icons

tallui-flags-origin uses Blade Icons under the hood. Please refer to [the Blade Icons readme](https://github.com/blade-ui-kit/blade-icons) for additional functionality. We also recommend to [enable icon caching](https://github.com/blade-ui-kit/blade-icons#caching) with this library.

## Configuration

tallui-flags-origin also offers the ability to use features from Blade Icons like default classes, default attributes, etc. If you'd like to configure these, publish the `tallui-flags-origin.php` config file:

```bash
php artisan vendor:publish --tag=tallui-flags-origin-config
```

## Usage

Icons can be used as self-closing Blade components which will be compiled to SVG icons:

```blade
<x-talluiflagsorigin-iconname />
```

You can also pass classes to your icon components:

```blade
<x-talluiflagsorigin-iconname class="w-6 h-6 text-gray-500"/>
```

And even use inline styles:

```blade
<x-talluiflagsorigin-iconname style="color: #555"/>
```

### Raw SVG Icons

If you want to use the raw SVG icons as assets, you can publish them using:

```bash
php artisan vendor:publish --tag=tallui-flags-origin --force
```

Then use them in your views like:

```blade
<img src="{{ asset('vendor/tallui-flags-origin/iconname.svg') }}" width="10" height="10"/>
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/usetall/tallui/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](https://github.com/usetall/tallui/security/policy) on how to report security vulnerabilities.

## Credits

This package is based on Package Skeleton Laravel from [Spatie](https://spatie.be/products). If you are a Laravel developer, their services, products and trainings are for you. Otherwise they love post cards.

-   [TALLUI Devs](https://github.com/orgs/usetall/people)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
