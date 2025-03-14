![Moox Localization](https://github.com/mooxphp/moox/raw/main/art/banner/localization-package.jpg)

# Moox Localization

This is a package for Laravel to handle localization and requires the package [astrotomic/laravel-translatable](https://github.com/Astrotomic/laravel-translatable).

## Quick Installation

These two commmands are all you need to install the package:

```bash
composer require moox/localization
php artisan localization:install
```

Curious what the install command does? See manual installation below.

## What it does

- Will create a new table `localizations` with the following columns:
    - `language_id`
    - `routing_path`
    - `title`
    - `description`
    - `keywords`
    - `author`
    - `created_at`
    - `updated_at`

- Has dependencies to the `astrotomic/laravel-translatable` and `moox/core` package.
- Has Language Switcher Livewire Component and LocalizationPanelProvider.


#### LocalizationPanelProvider

To use the LocalizationPanelProvider, you need to enable it in the config file:

```php
    'enable-panel' => true,
``` 
now the panel will be available at `/localization`


#### Language Switcher
Include a Language switcher which wil rely on your created locales. 
To include it in filament panel 
```
->renderHook(
                \Filament\View\PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => \Illuminate\Support\Facades\Blade::render('@livewire(\'language-switch\',[\'context\'=>\'backend\'])'),
            );

```
or just use the livewire component in your blade view:
```blade
@livewire('language-switch',['context'=>'backend'])
```

#### LanguageMiddleware

The LanguageMiddleware is used to set a session cookie for the language.

#### Tabs and Translation

Moox Core features like Dynamic Tabs and Translatable Config. See the config file for more details, but as a quick example:

```php
            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Resource table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [
                        [
                            'field' => 'deleted_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],
                'published' => [
                    'label' => 'trans//core::core.published',
                    'icon' => 'gmdi-check-circle',
                    'query' => [
                        [
                            'field' => 'publish_at',
                            'operator' => '<=',
                            'value' => function () {
                                return now();
                            },
                        ],
                        [
                            'field' => 'deleted_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],
                'scheduled' => [
                    'label' => 'trans//core::core.scheduled',
                    'icon' => 'gmdi-schedule',
                    'query' => [
                        [
                            'field' => 'publish_at',
                            'operator' => '>',
                            'value' => function () {
                                return now();
                            },
                        ],
                        [
                            'field' => 'deleted_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],
                'draft' => [
                    'label' => 'trans//core::core.draft',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'publish_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                        [
                            'field' => 'deleted_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],
                'deleted' => [
                    'label' => 'trans//core::core.deleted',
                    'icon' => 'gmdi-delete',
                    'query' => [
                        [
                            'field' => 'deleted_at',
                            'operator' => '!=',
                            'value' => null,
                        ],
                    ],
                ],
            ],
        ],
```

All options for Tabs are explained in [Moox Core docs](https://github.com/mooxphp/core/blob/main/README.md#dynamic-tabs).

## Manual Installation

Instead of using the install-command `php artisan localization:install` you are able to install this package manually step by step:

```bash
// Publish and run the migrations:
php artisan vendor:publish --tag="localization-migrations"
php artisan migrate

// Publish the config file with:
php artisan vendor:publish --tag="localization-config"
```
## use it 

We are requiring astrotomic/laravel-translatable
to use it see doc: https://docs.astrotomic.info/laravel-translatable

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
