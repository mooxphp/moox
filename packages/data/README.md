![Moox DataLanguages](https://github.com/mooxphp/moox/raw/main/art/banner/data-package.jpg)

# Moox DataLanguages

Some Static Data

## Quick Installation

### Using the Moox app (recommended)

If you use the Moox application, install the package and run the unified Moox installer:

```bash
composer require moox/data
```

Before importing static country data, add a REST Countries API key to your `.env` (see [REST Countries API key](#rest-countries-api-key) below).

```bash
php artisan moox:install
```

`moox:install` publishes configs, runs migrations, and registers Filament plugins for Moox packages. For this package it can also run the **Static Data** custom installer (countries, languages, timezones, currencies from REST Countries) when that step is included in the install flow.

You do **not** need a separate package-specific install command when using `moox:install`. If static data is not imported during install, configure your API key and run the import command in [REST Countries API key](#rest-countries-api-key) below.

Curious what the installer does? See the [Moox installer docs](https://github.com/mooxphp/core/blob/main/src/Installer/README.md) or [manual installation](#manual-installation) below.

## REST Countries API key

Country, currency, language, and timezone data is imported from the [REST Countries API](https://restcountries.com/docs). The API requires an authenticated API key.

1. Sign up for a free API key at [restcountries.com/sign-up](https://restcountries.com/sign-up).
2. Add the key to your `.env` file:

```dotenv
REST_COUNTRIES_API_KEY=your_api_key_here
```

3. Optionally publish the package configuration:

```bash
php artisan vendor:publish --tag="data-config"
```

The published `config/rest-countries.php` file also supports:

```php
return [
    'api_key' => env('REST_COUNTRIES_API_KEY'),
    'base_url' => env('REST_COUNTRIES_BASE_URL', 'https://api.restcountries.com/countries/v5'),
    'timeout' => (int) env('REST_COUNTRIES_TIMEOUT', 60),
    'page_limit' => (int) env('REST_COUNTRIES_PAGE_LIMIT', 100),
];
```

### Free tier and request usage

REST Countries offers a [free plan](https://restcountries.com/plans) with **500 API requests per month** (quota resets on your billing anniversary). On the free plan, each list request can return at most **100 countries** per page (`page_limit` defaults to `100`).

A single `moox:data:import-static` run uses **3 REST Countries requests** with the default settings: the API returns about 250 countries, paginated at 100 per request (`offset` 0, 100, 200). The import also makes **one additional HTTP request** to ApiCountries for language native names; that call is separate and does not count against your REST Countries quota.

At 3 requests per import, the free tier allows roughly **160 full imports per month** if REST Countries is the only consumer of your quota.

After configuring your key, import static data (if not already done via `moox:install`) or refresh it later:

```bash
php artisan moox:data:import-static --sync
```

## What it does

Static Data for country, language, Timzones, and Currencies. 


also see ==> https://filamentphp.com/docs/3.x/support/render-hooks

To include it in your frontend jsut use the livewire notation 

```
                <livewire:language-switch />
```

On default the component will assume the context is frontend. If thats not the chase provide the context withing the componenten.

### Using the Template

### Config

After that the Resource is highly configurable.

#### DataPanelProvider

To use the DataPanelProvider, you need to enable it in the config file:

```php
    'enable-panel' => true,
``` 
now the panel will be available at `/data`


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

#### Item Types

The item also support 'item' types, means you are able to configure selectable types for your Entity. By default, we provide "Post" and "Page" as example. If you don't want to use types, just empty the array and the field and column become invisible.

```php
    /*
    |--------------------------------------------------------------------------
    | Item Types
    |--------------------------------------------------------------------------
    |
    | This array contains the types of items entities. You can delete
    | the types you don't need and add new ones. If you don't need
    | types, you can empty this array like this: 'types' => [],
    |
    */

    'types' => [
        'post' => 'Post',
        'page' => 'Page',
    ],
```

#### Author Model

You can configure the user model used for displaying Authors. By default it is tied to App User:

```php
    /*
    |--------------------------------------------------------------------------
    | Author Model
    |--------------------------------------------------------------------------
    |
    | This sets the user model that can be used as author. It should be an
    | authenticatable model and support the morph relationship.
    | It should have fields similar to Moox User or WpUser.
    |
    */

    'author_model' => \App\Models\User::class,
```

You may probably use Moox User

```php
    'author_model' => \Moox\User\Models\User::class,
```

or Moox Press User instead:

```php
    'author_model' => \Moox\Press\Models\WpUser::class,
```

<!--/whatdoes-->

## Manual Installation

**Note:** If you use the Moox app, run `php artisan moox:install` instead—it installs all packages and runs their migrations in one go. The steps below are only for installing this package without the unified installer.

Instead of using `moox:install`, you can install this package manually step by step:

```bash
// Publish and run the migrations:
php artisan vendor:publish --tag="data-migrations"
php artisan migrate

// Publish the config file with:
php artisan vendor:publish --tag="data-config"
```

Then configure your REST Countries API key and import static data as described in [REST Countries API key](#rest-countries-api-key).

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
