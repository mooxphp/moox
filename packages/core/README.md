![Moox Core](https://github.com/mooxphp/moox/raw/main/art/banner/core.jpg)

# Moox Core

The Moox Core package provides common features, used by all Moox packages. If you use Moox Builder to create your own Moox package, these features are already included and pre-configured.

## Features

-   Dynamic Tabs - create your own tabs just by a few lines of configuration
-   Dynamic Taxonomies - attach hierarchical or flat taxonomies to your items

and some other mentioned below in Traits, Services and Forms.

## Installation

If you want to install Moox Core (normally not necessary, because this package is required by all other Moox packages), you can:

```bash
composer require moox/core
php artisan mooxcore:install
```

## Requires

Moox Core requires these packages:

-   https://filamentphp.com/ and https://laravel.com/ usually in the latest stable version, see composer.json
-   https://github.com/Pharaonic/laravel-readable - for formatting numbers and dates to be human readable
-   https://github.com/ryangjchandler/filament-progress-column - to use progress bars in Filament tables
-   https://github.com/codeat3/blade-google-material-design-icons - we use Google Material Design Icons

## Traits

### TabsInListPage

This trait provides functionality for creating dynamic tabs in Filament resources.

#### Key Methods

-   `getDynamicTabs(string $configPath, string $modelClass)`: Returns an array of dynamic tabs based on configuration.

#### Usage

The DynamicTabs trait is already implemented in all Moox packages including Moox Builder. If you want to implement this feature in your existing package:

```php
use Moox\Core\Traits\Tabs\TabsInListPage;

class ListItems extends ListRecords
{
    use TabsInListPage;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('package.resources.item.tabs', YourModel::class);
    }
}
```

#### Configuration

Provide a configuration like:

```php
    'resources' => [
        'item' => [

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
                    'query' => [],
                ],
                'documents' => [
                    'label' => 'trans//core::core.documents',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'expiry_job',
                            'operator' => '=',
                            'value' => 'Documents',
                        ],
                    ],
                ],
            ],
        ],
    ],
```

#### Disable Tabs

If you want to disable tabs for this resource, just do a

```php
            'tabs' => [],
```

#### Basic Tabs

A pretty basic example:

```php
            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                'user' => [
                    'label' => 'trans//core::core.user_session',
                    'icon' => 'gmdi-account-circle',
                    'query' => [
                        [
                            'field' => 'user_id',
                            'operator' => '!=',
                            'value' => null,
                        ],
                    ],
                  	'visible' => true,
                ],
            ],
```

#### Visibility

The `visible`-option is not necessary, as it defaults to true. But if you want to toggle the visibility of a Tab, you can use a Closure like this:

```php
										'visible' => in_array(rtrim(env('APP_URL'), '/'), [
												'https://visible.test',
                        'http://localhost',
                    ]),
```

#### Queries

See [Queries in Config](#queriesinconfig) on how to use the query feature.

#### Translation

See [Translatable Config](#translatableconfig) on how to use the trans feature.

### QueriesInConfig

This trait allows building complex queries from configuration arrays.

#### Key Methods

-   `buildQueryFromConfig(array $queryConfig)`: Builds a query based on the provided configuration.

#### Usage

```php
use Moox\Core\Traits\QueriesInConfig;

class YourClass
{
    use QueriesInConfig;

    public function someMethod()
    {
        $queryConfig = [
            ['field' => 'status', 'operator' => '=', 'value' => 'open'],
        ];
        $query = $this->buildQueryFromConfig($queryConfig);
    }
}
```

#### Configuration

Dynamic Tabs uses the QueriesInConfig Trait, that means you can build queries like this:

The simplest query is for the All tab, of course:

```php
					'query' => [],
```

All other queries require three parameters:

```php
                    'query' => [
                        [
                            'field' => 'status',
                            'operator' => '=',
                            'value' => 'open',
                        ],
                    ],
```

Add more, if you need:

```php
                    'query' => [
                        [
                            'field' => 'post_type',
                            'operator' => '=',
                            'value' => 'Post',
                        ],
                        [
                            'field' => 'deleted',
                            'operator' => '!=',
                            'value' => 'false',
                        ],
                    ],
```

We DON'T YET support relations nor the like operator. If you're in the need, we would be happy to merge a PR :-)

```php
                    // TODO: not implemented yet
										'query' => [
                        [
                            'field' => 'user_name',
                            'relation' => 'user',
                            'operator' => 'like',
                            'value' => 'Alf',
                        ],
                    ],
```

The value of a query accepts a closure. Following example is perfect for a "My Tab" as it filters for the current user:

```php
                    'query' => [
                        [
                            'field' => 'user_id',
                            'operator' => '=',
                            'value' => function () {
                                return auth()->user()->id;
                            },
                        ],
                    ],
```

Finally, a special idea and therefor NOT YET implemented: if the value contains a class and a forth parameter `hide-if-not-exists`set to true, Moox will check, if the class exists and otherwise hide the tab. That allows us to register buttons for packages, that are not necessarily required.

```php
                    // TODO: not implemented yet
										'query' => [
                        [
                            'field' => 'status',
                            'operator' => '=',
                            'value' => 'Moox\Press\Models\WpUser',
                            'hide-if-not-exists' => true,
                        ],
                    ],
```

An practical example (works for Sessions, Devices and other user-related entities):

```php
            'tabs' => [
	            'mine' => [
                    'label' => 'My Sessions',
                    'icon' => 'gmdi-account-circle',
                    'query' => [
                        [
                            'field' => 'user_id',
                            'operator' => '=',
                            'value' => function () {
                                return auth()->user()->id;
                            },
                        ],
                    ],
                ],
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                'user' => [
                    'label' => 'User Sessions',
                    'icon' => 'gmdi-account-circle',
                    'query' => [
                        [
                            'field' => 'user_type',
                            'operator' => '=',
                            'value' => 'Moox\User\Models\User',
                        ],
                    ],
                ],
                'wpuser' => [
                    'label' => 'WordPress Sessions',
                    'icon' => 'gmdi-account-circle',
                    'query' => [
                        [
                            'field' => 'user_type',
                            'operator' => '=',
                            'value' => 'Moox\Press\Models\WpUser',
                        ],
                    ],
                ],
                'anonymous' => [
                    'label' => 'Anonymous Sessions',
                    'icon' => 'gmdi-no-accounts',
                    'query' => [
                        [
                            'field' => 'user_id',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],
            ],
```

And finally the most-known mistake, throws "Cannot access offset of type string on string":

```php
        'query' => [
						'field' => 'user_id',
						'operator' => '=',
						'value' => null,
        ],
```

So don't forget to put the query in an extra array, even if it is a single query.

As mentioned, the QueriesInConfig trait is used in TabsInListPage, another Trait in Moox Core. Please code dive there, to see how to implement the Feature from outside Moox.

### TranslatableConfig

This trait provides functionality for translating configuration values.

#### Key Methods

-   `translateConfigurations()`: Translates configuration values based on defined translation keys.

#### Usage

```php
use Moox\Core\Traits\TranslatableConfig;

class YourServiceProvider extends ServiceProvider
{
    use TranslatableConfig;

    public function boot()
    {
        $this->translateConfigurations();
    }
}
```

#### Configuration

A simple but useful feature is the TranslationsInConfig Trait that is used a lot in our config files, as seen with Tabs:

```php
                    'label' => 'trans//core::core.all',
```

Translations of our packages are generally organized in Moox Core. Only few of our packages ship with own translation files. These packages are registered in the core.php configuration file. If you develop a custom package (preferably using Moox Builder) you need to add your custom package to the [Package Registration](#Package-registration).

### RequestInModel

This trait allows access to request data within models.

#### Key Methods

-   `getRequestData(string $key)`: Retrieves request data for the given key.

#### Usage

```php
use Moox\Core\Traits\RequestInModel;

class YourModel extends Model
{
    use RequestInModel;

    public function someMethod()
    {
        $data = $this->getRequestData('some_key');
    }
}
```

The RequestInModel Trait is currently used by all Moox Press packages. It allows us to use the request data in some of our models. You can code dive into Moox\Press\Models\WpTerm.php, to find more code examples. The basic implementation looks like this:

```php
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\RequestInModel;

class WpTerm extends Model
{
	use RequestInModel;

  $description = $wpTerm->getRequestData('description');
}
```

### GoogleIcons

This trait provides methods for using Google Material Design Icons in your application.

#### Key Methods

-   `useGoogleIcons()`: Configures the application to use Google Material Design Icons.

#### Usage

```php
use Moox\Core\Traits\GoogleIcons;

class YourServiceProvider extends ServiceProvider
{
    use GoogleIcons;

    public function boot()
    {
        $this->useGoogleIcons();
    }
}
```

As [Google Material Design Icons](https://blade-ui-kit.com/blade-icons?set=20) provides one of the largest sets of high quality icons, we decided to use them as default for Moox. The GoogleIcons Trait changes the default Filament Icons, too. It is used in the CoreServiceProvider like this:

You can disable Google Icons and use the Filament default icons instead, see [config](#Config).

### TaxonomyInModel

This trait provides functionality for models to work with dynamic taxonomies.

#### Key Methods

-   `taxonomy(string $taxonomy)`: Returns a MorphToMany relationship for the given taxonomy.
-   `syncTaxonomy(string $taxonomy, array $ids)`: Syncs the given taxonomy with the provided IDs.

#### Usage

```php
use Moox\Core\Traits\Taxonomy\TaxonomyInModel;

class YourItem extends Model
{
    use TaxonomyInModel;

    protected function getResourceName(): string
    {
        return 'youritem'; // name your item
    }

    // ... other model code ...

    // Add a polymorphic relation
    public function youritemables(string $type): MorphToMany
    {
        return $this->morphedByMany($type, 'youritemable');
    }

    // Delete relations on (soft)-delete
    public function detachAllYouritemables(): void
    {
        DB::table('youritemables')->where('youritem_id', $this->id)->delete();
    }

  	// Needed for deletion
    protected static function booted(): void
    {
        static::deleting(function (YourItem $youritem) {
            $youritem->detachAllYouritemables();
        });
    }
}
```

```
> [!WARNING]
> We do not soft-delete the polymorphics, so if you restore a Taxonomy, these are lost.
```

### TaxonomyInResource

This trait provides methods for generating Filament form fields, table columns, and filters for dynamic taxonomies.

#### Key Methods

-   `getTaxonomyFields()`: Returns an array of Filament form fields for taxonomies.
-   `getTaxonomyFilters()`: Returns an array of Filament table filters for taxonomies.
-   `getTaxonomyColumns()`: Returns an array of Filament table columns for taxonomies.
-   `handleTaxonomies(Model $record, array $data)`: Handles saving taxonomy relationships.

#### Usage

```php
use Moox\Core\Traits\Taxonomy\TaxonomyInResource;

class YourResource extends Resource
{
    use TaxonomyInResource;

    public static function form(Form $form): Form
    {
        return $form->schema([
            // ... other fields ...
            ...static::getTaxonomyFields(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ... other columns ...
                ...static::getTaxonomyColumns(),
            ])
            ->filters([
                // ... other filters ...
                ...static::getTaxonomyFilters(),
            ]);
    }
}
```

### TaxonomyInPages

This trait provides methods for handling dynamic taxonomies in Filament resource pages.

#### Key Methods

-   `handleTaxonomies()`: Handles saving taxonomy relationships.
-   `mutateFormDataBeforeFill(array $data)`: Prepares taxonomy data for form filling.
-   `refreshTaxonomyFormData()`: Refreshes taxonomy form data after save.

#### Usage

```php
use Moox\Core\Traits\Taxonomy\TaxonomyInPages;

class EditYourModel extends EditRecord
{
    use TaxonomyInPages;

    // ... other page code ...
}
```

### Log Level

Log Level is a useful feature to debug things in Moox, even when you're in production, or get your logs silent while developing.

```php

    $this->logDebug('This is a debug message');
    $this->logInfo('This is an info message');

```

You can adjust the log level and whether to log in production in Moox Core's [Config](#Logging).

## Services

### TaxonomyService

This service manages taxonomy configurations and provides utility methods for working with taxonomies.

#### Key Methods

-   `setCurrentResource(string $resource)`: Sets the current resource context.
-   `getTaxonomies()`: Returns all configured taxonomies for the current resource.
-   `getTaxonomyModel(string $taxonomy)`: Returns the model class for a given taxonomy.
-   `validateTaxonomy(string $taxonomy)`: Validates a taxonomy configuration.

#### Usage

```php
use Moox\Core\Services\TaxonomyService;

class YourClass
{
    protected TaxonomyService $taxonomyService;

    public function __construct(TaxonomyService $taxonomyService)
    {
        $this->taxonomyService = $taxonomyService;
        $this->taxonomyService->setCurrentResource('your_resource');
    }

    public function someMethod()
    {
        $taxonomies = $this->taxonomyService->getTaxonomies();
        // ... use taxonomies ...
    }
}
```

### DnsLookupService

This service provides DNS lookup functionality.

#### Key Methods

-   `getIpAddress(string $domain)`: Retrieves the IP address for a given domain.

#### Usage

```php
use Moox\Core\Services\DnsLookupService;

class YourClass
{
    public function someMethod(string $domain)
    {
        $ipAddress = DnsLookupService::getIpAddress($domain);
    }
}
```

That Service is currently used in Moox Sync's PlatformResource like so:

```php
use Moox\Core\Services\DnsLookupService;

class PlatformResource extends Resource
{
		public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                  TextInput::make('domain')
                        ->label(__('core::core.domain'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->live(debounce: 500)
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (empty($state)) {
                                $set('ip_address', 'The host is not resolvable');
                            } else {
                                $ipAddress = DnsLookupService::getIpAddress($state);
                                $set('ip_address', $ipAddress ?: 'The host is not resolvable');
                            }
                        })
                  ]),
            ]),
        ]);

```

### TaxonomyCreateForm

This class provides a schema for creating taxonomy entries.

#### Usage

```php
use Moox\Tag\Forms\TaxonomyCreateForm;

class YourResource extends Resource
{
    public static function form(Form $form): Form
    {
        return $form->schema([
            // ... other fields ...
            ...TaxonomyCreateForm::getSchema(),
        ]);
    }
}
```

This form includes fields for title and slug, as these are needed for creating Taxonomies. As your taxonomy might need further fields, you can use your own form to create your custom taxonomies.

## APIs

### Core API

The Core API `api/core` provides all available packages (and their configuration).

It is currently not used, and may be changed or removed.

### Models API

The Models API `api/models` provides all available and loaded models. It is used by Moox Sync, for example.

The Models API is work in progress. It probably will be secured.

### Shared Hosting API

The Shared Hosting API `schedule/run` is used to run scheduled tasks in shared hosting environments.

https://yourdomain.com/schedule/run?token=secure

If you want to use the Shared Hosting API, you need to set the `SHARED_HOSTING_ENABLED` [config](#Shared-Hosting) to `true` and the `SHARED_HOSTING_TOKEN` config to a secure token.

## Configuration

### Package Registration

Register Moox packages and custom packages in the `config/core.php` file:

Moox has a simple package registration. To ensure that some features of Moox Core are only available for known packages, all Moox packages and all custom packages, created with Moox Builder, need to register here:

```php
    /*
    |--------------------------------------------------------------------------
    | Moox Packages
    |--------------------------------------------------------------------------
    |
    | This config array registers all known Moox packages. You may add own
    | packages to this array. If you use Moox Builder, these packages
    | work out of the box. Adding a non-compatible package fails.
    |
    */

    'packages' => [
        'audit' => 'Moox Audit',
        'builder' => 'Moox Builder',
        'core' => 'Moox Core',
        'expiry' => 'Moox Expiry',
        'jobs' => 'Moox Jobs',
        'login-link' => 'Moox Login Link',
        'notifications' => 'Moox Notifications',
        'page' => 'Moox Page',
        'passkey' => 'Moox Passkey',
        'permission' => 'Moox Permission',
        'press' => 'Moox Press',
        'press-wiki' => 'Moox Press Wiki',
        'security' => 'Moox Security',
        'sync' => 'Moox Sync',
        'training' => 'Moox Trainings',
        'user' => 'Moox User',
        'user-device' => 'Moox User Device',
        'user-session' => 'Moox User Session',
    ],
];
```

You can publish the Moox Core configuration file and add own packages:

```bash
php artisan vendor:publish --tag="core-config"
```

but remember to update the Array regularly then, to allow newer Moox packages to work flawlessly.

### Google Icons

You can disable Google Icons, and use the Filament default iconset (Heroicons) instead.

This disables the replacement of the Filament core icons, done in Core, as well as the individual icons of most of our packages. Some packages will remain with Google Icons, because there is no corresponding icon in the Heroicon set.

```php
    /*
    |--------------------------------------------------------------------------
    | Google Icons
    |--------------------------------------------------------------------------
    |
    | We use Google Material Design Icons, but if you want to use the
    | Heroicons, used by Filament as default, you can disable the
    | Google Icons here. This will affect the whole application.
    |
    */

    'google_icons' => true,
```

### Logging

You can adjust the log level and whether to log in production. Currently used by Moox Sync and soon by other Moox packages, too.

```php
    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | This config array sets the logging level and whether to log in
    | production. It is used by some Moox packages where verbose
    | logging is a good thing while implementing complex stuff.
    |
    */

    'logging' => [
        'verbose_level' => env('VERBOSE_LEVEL', 0), // 0: Off, 1: Debug, 2: Info, 3: All
        'log_in_production' => env('LOG_IN_PRODUCTION', false),
    ],
```

### Shared Hosting

You can enable shared hosting features. This is useful if you want to run scheduled tasks in shared hosting environments. It allows you to run the `queue:work` and 'schedule:run' command from an URL

```php
    /*
    |--------------------------------------------------------------------------
    | Shared Hosting
    |--------------------------------------------------------------------------
    |
    | This config array sets the shared hosting token. This token is used to
    | authenticate requests from shared hosting environments.
    |
    */

    'shared_hosting' => [
        'enabled' => env('SHARED_HOSTING_ENABLED', false),
        'token' => env('SHARED_HOSTING_TOKEN', 'secret'),
    ],
```

## Assets

Moox Core provides a couple of (non-publishable) assets, loaded by a dynamic route. All images and assets in /public can be used like `url('/moox/core/assets/noimage.svg'`.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
