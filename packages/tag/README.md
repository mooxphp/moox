![Moox Tag](https://github.com/mooxphp/moox/raw/main/art/banner/tag.jpg)

# Moox Tag

A simple Tag system for Filament.

## Quick Installation

These two commmands are all you need to install the package:

```bash
composer require moox/tag
php artisan mooxtag:install
```

Curious what the install command does? See manual installation below.

## What it does

<!--whatdoes-->

This Laravel Package Template can be used to create a package including a powerful Filament resource called Tag.

![Moox Tag Tag](https://github.com/mooxphp/moox/raw/main/art/screenshot/tag-item.jpg)

Name and table for the Resource can be changed while building your package.

### Using the Template

1. Go to https://github.com/mooxphp/tag
2. Press the `Use this template` button
3. Create a new repository based on the template
4. Clone the repository locally
5. Run `php build.php`in the repo's directory and follow the steps
    - Author Name (Default: Moox Developer): Your Name
    - Author Email (Default: dev@moox.org): your@mail.com
    - Package Name (Default: Blog Package): Your Package
    - Package Description (Default: This is my package Blog Package)
    - Package Entity (Default: Tag): e.g. Post
    - Tablename (Default: tags): e.g. posts

After building the package, you can push the changes to GitHub and create an installable package on Packagist.org. Don't forget to adjust the README to your composer namespace.

### Config

After that the Resource is highly configurable.

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

#### Tag Types

The item also support 'item' types, means you are able to configure selectable types for your Entity. By default, we provide "Post" and "Page" as example. If you don't want to use types, just empty the array and the field and column become invisible.

```php
    /*
    |--------------------------------------------------------------------------
    | Tag Types
    |--------------------------------------------------------------------------
    |
    | This array contains the types of tags entities. You can delete
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

    'user_model' => \App\Models\User::class,
```

You may probably use Moox User

```php
    'user_model' => \Moox\User\Models\User::class,
```

or Moox Press User instead:

```php
    'user_model' => \Moox\Press\Models\WpUser::class,
```

<!--/whatdoes-->

## Manual Installation

Instead of using the install-command `php artisan mooxtag:install` you are able to install this package manually step by step:

```bash
// Publish and run the migrations:
php artisan vendor:publish --tag="tag-migrations"
php artisan migrate

// Publish the config file with:
php artisan vendor:publish --tag="tag-config"
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
