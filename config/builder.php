<?php

/*
|--------------------------------------------------------------------------
| Moox Configuration
|--------------------------------------------------------------------------
|
| This configuration file uses translatable strings. If you want to
| translate the strings, you can do so in the language files
| published from moox_core. Example:
|
| 'trans//core::core.all',
| loads from common.php
| outputs 'All'
|
*/

return [

    'contexts' => [
        'custom' => [
            'base_path' => app_path('Custom'),
            'base_namespace' => 'App',
            'generators' => [
                'model' => [
                    'path' => '%BasePath%\Models',
                    'namespace' => '%BaseNamespace%\\Models',
                    'template' => __DIR__ . '/../src/Templates/Entity/model.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\ModelGenerator::class,
                ],
                'resource' => [
                    'path' => '%BasePath%\Resources',
                    'namespace' => '%BaseNamespace%\\Resources',
                    'template' => __DIR__ . '/../src/Templates/Entity/resource.php.stub',
                    'page_templates' => [
                        'List' => __DIR__ . '/../src/Templates/Entity/pages/list.php.stub',
                        'Create' => __DIR__ . '/../src/Templates/Entity/pages/create.php.stub',
                        'Edit' => __DIR__ . '/../src/Templates/Entity/pages/edit.php.stub',
                        'View' => __DIR__ . '/../src/Templates/Entity/pages/view.php.stub',
                    ],
                    'generator' => \Moox\Builder\Generators\Entity\ResourceGenerator::class,
                ],
                'migration' => [
                    'path' => 'database\migrations',
                    'template' => __DIR__ . '/../src/Templates/Entity/migration.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\MigrationGenerator::class,
                ],
                'plugin' => [
                    'path' => '%BasePath%\Filament\Plugins',
                    'namespace' => '%BaseNamespace%\\Filament\\Plugins',
                    'template' => __DIR__ . '/../src/Templates/Entity/plugin.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\PluginGenerator::class,
                ],
                'translation' => [
                    'path' => 'lang\%locale%\entities',
                    'template' => __DIR__ . '/../src/Templates/Entity/translation.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\TranslationGenerator::class,
                ],
                'config' => [
                    'path' => 'config\entities',
                    'template' => __DIR__ . '/../src/Templates/Entity/config.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\ConfigGenerator::class,
                ],
            ],
        ],
        'app' => [
            'base_path' => app_path('app/Locale'),
            'base_namespace' => 'App\\Locale',
            'generators' => [
                'model' => [
                    'path' => '%BasePath%\app\Locale\Models',
                    'namespace' => '%BaseNamespace%\\Locale\\Models',
                    'template' => __DIR__ . '/../packages/builder/src/Templates/Entity/model.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\ModelGenerator::class,
                ],
                'resource' => [
                    'path' => '%BasePath%\app\Locale\Resources',
                    'namespace' => '%BaseNamespace%\\Locale\\Resources',
                    'template' => __DIR__ . '/../packages/builder/src/Templates/Entity/resource.php.stub',
                    'page_templates' => [
                        'List' => __DIR__ . '/../packages/builder/src/Templates/Entity/pages/list.php.stub',
                        'Create' => __DIR__ . '/../packages/builder/src/Templates/Entity/pages/create.php.stub',
                        'Edit' => __DIR__ . '/../packages/builder/src/Templates/Entity/pages/edit.php.stub',
                        'View' => __DIR__ . '/../packages/builder/src/Templates/Entity/pages/view.php.stub',
                    ],
                    'generator' => \Moox\Builder\Generators\Entity\ResourceGenerator::class,
                ],
                'plugin' => [
                    'path' => '%BasePath%\app\Locale\Plugins',
                    'namespace' => '%BaseNamespace%\\Locale\\Plugins',
                    'template' => __DIR__ . '/../packages/builder/src/Templates/Entity/plugin.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\PluginGenerator::class,
                ],
                // 'migration' => [
                //    'path' => '%BasePath%\app\Builder\Locale\database\migrations',
                //    'template' => __DIR__ . '/../packages/builder/src/Templates/Entity/migration.php.stub',
                //    'generator' => \Moox\Builder\Generators\Entity\MigrationGenerator::class,
                // ],
                'translation' => [
                    'path' => 'Locale\lang\%locale%\entities',
                    'template' => __DIR__ . '/../packages/builder/src/Templates/Entity/translation.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\TranslationGenerator::class,
                ],
                'config' => [
                    'path' => '%BasePath%\app\Locale\config\entities',
                    'template' => __DIR__ . '/../src/Templates/Entity/config.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\ConfigGenerator::class,
                ],
            ],
        ],
        /*
        'package' => [
            'base_path' => '$PackagePath',
            'base_namespace' => '$PackageNamespace',
            'generators' => [
                'model' => [
                    'path' => '%BasePath%\src\Models',
                    'namespace' => '%BaseNamespace%\\Models',
                    'template' => __DIR__.'/../src/Templates/Entity/model.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\ModelGenerator::class,
                ],
                'resource' => [
                    'path' => '%BasePath%\src\Resources',
                    'namespace' => '%BaseNamespace%\\Resources',
                    'template' => __DIR__.'/../src/Templates/Entity/resource.php.stub',
                    'page_templates' => [
                        'List' => __DIR__.'/../src/Templates/Entity/pages/list.php.stub',
                        'Create' => __DIR__.'/../src/Templates/Entity/pages/create.php.stub',
                        'Edit' => __DIR__.'/../src/Templates/Entity/pages/edit.php.stub',
                        'View' => __DIR__.'/../src/Templates/Entity/pages/view.php.stub',
                    ],
                    'generator' => \Moox\Builder\Generators\Entity\ResourceGenerator::class,
                ],
                'migration_stub' => [
                    'path' => '%BasePath%\database\migrations',
                    'template' => __DIR__.'/../src/Templates/Entity/migration.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\MigrationGenerator::class,
                ],
                'plugin' => [
                    'path' => '%BasePath%\src',
                    'namespace' => '%BaseNamespace%',
                    'template' => __DIR__.'/../src/Templates/Entity/plugin.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\PluginGenerator::class,
                ],
                'translation' => [
                    'path' => '%BasePath%\resources\lang\entities',
                    'template' => __DIR__.'/../src/Templates/Entity/translation.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\TranslationGenerator::class,
                ],
                'config' => [
                    'path' => '%BasePath%\config\entities',
                    'template' => __DIR__.'/../src/Templates/Entity/config.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\ConfigGenerator::class,
                ],
            ],
        ],
        */
        'preview' => [
            'base_path' => 'Locale',
            'base_namespace' => 'Locale',
            'generators' => [
                'model' => [
                    'path' => '%BasePath%\Locale\Models',
                    'namespace' => '%BaseNamespace%\\Locale\\Models',
                    'template' => __DIR__ . '/../packages/builder/src/Templates/Entity/model.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\ModelGenerator::class,
                ],
                'resource' => [
                    'path' => '%BasePath%\Locale\Resources',
                    'namespace' => '%BaseNamespace%\\Locale\\Resources',
                    'template' => __DIR__ . '/../packages/builder/src/Templates/Entity/resource.php.stub',
                    'page_templates' => [
                        'List' => __DIR__ . '/../packages/builder/src/Templates/Entity/pages/list.php.stub',
                        'Create' => __DIR__ . '/../packages/builder/src/Templates/Entity/pages/create.php.stub',
                        'Edit' => __DIR__ . '/../packages/builder/src/Templates/Entity/pages/edit.php.stub',
                        'View' => __DIR__ . '/../packages/builder/src/Templates/Entity/pages/view.php.stub',
                    ],
                    'generator' => \Moox\Builder\Generators\Entity\ResourceGenerator::class,
                ],
                'plugin' => [
                    'path' => '%BasePath%\Locale\Plugins',
                    'namespace' => '%BaseNamespace%\\Locale\\Plugins',
                    'template' => __DIR__ . '/../packages/builder/src/Templates/Entity/plugin.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\PluginGenerator::class,
                ],
                'translation' => [
                    'path' => '%BasePath%\Locale\lang\%locale%\previews',
                    'template' => __DIR__ . '/../packages/builder/src/Templates/Entity/translation.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\TranslationGenerator::class,
                ],
                'config' => [
                    'path' => '%BasePath%\Locale\config\previews',
                    'template' => __DIR__ . '/../packages/builder/src/Templates/Entity/config.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\ConfigGenerator::class,
                ],
                'migration' => [
                    'path' => '%BasePath%\Locale\database\migrations',
                    'template' => __DIR__ . '/../packages/builder/src/Templates/Entity/migration.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\MigrationGenerator::class,
                ],
            ],
            'should_migrate' => true,
        ],

    ],

    'presets' => [
        'light-item' => [
            'class' => \Moox\Builder\Presets\LightItemPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'simple-item' => [
            'class' => \Moox\Builder\Presets\SimpleItemPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'language-item' => [
            'class' => \App\Locale\Presets\StaticLanguagePreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'timezone-item' => [
            'class' => \App\Locale\Presets\StaticTimezonePreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'country-item' => [
            'class' => \App\Locale\Presets\CountryPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'locale-item' => [
            'class' => \App\Locale\Presets\LocalePreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'currency-item' => [
            'class' => \App\Locale\Presets\CurrencyPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'pivot-item' => [
            'class' => \App\Locale\Presets\PivotPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'country-currency-item' => [
            'class' => \App\Locale\Presets\CountryCurrencyPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'time-country-item' => [
            'class' => \App\Locale\Presets\CountryTimezonePreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'soft-delete-item' => [
            'class' => \Moox\Builder\Presets\SoftDeleteItemPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'full-item' => [
            'class' => \Moox\Builder\Presets\FullItemPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'static-language' => [
            'class' => \App\Locale\Presets\StaticLanguagePreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | The following configuration is done per Filament resource.
    |
    */

    'resources' => [
        'item' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//builder::translations.item',
            'plural' => 'trans//builder::translations.items',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Builder table. They are optional, but
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

            /*
            |--------------------------------------------------------------------------
            | Taxonomies
            |--------------------------------------------------------------------------
            |
            | This array contains the taxonomies that should be shown.
            | This is work in progress and not yet fully documented.
            |
            */

            'taxonomies' => [
                'categories' => [
                    'label' => 'Categories',
                    'model' => \Moox\Category\Models\Category::class,
                    'table' => 'categorizables',
                    'relationship' => 'categorizable',
                    'foreignKey' => 'categorizable_id',
                    'relatedKey' => 'category_id',
                    'createForm' => \Moox\Category\Forms\TaxonomyCreateForm::class,
                    'hierarchical' => true,
                ],
                'tags' => [
                    'label' => 'Tags',
                    'model' => \Moox\Tag\Models\Tag::class,
                    'table' => 'taggables',
                    'relationship' => 'taggable',
                    'foreignKey' => 'taggable_id',
                    'relatedKey' => 'tag_id',
                    'createForm' => \Moox\Tag\Forms\TaxonomyCreateForm::class,
                ],
            ],
        ],
        'full-item' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//builder::translations.full-item',
            'plural' => 'trans//builder::translations.full-items',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Builder table. They are optional, but
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

            /*
            |--------------------------------------------------------------------------
            | Taxonomies
            |--------------------------------------------------------------------------
            |
            | This array contains the taxonomies that should be shown.
            | This is work in progress and not yet fully documented.
            |
            */

            'taxonomies' => [
                'categories' => [
                    'label' => 'Categories',
                    'model' => \Moox\Category\Models\Category::class,
                    'table' => 'categorizables',
                    'relationship' => 'categorizable',
                    'foreignKey' => 'categorizable_id',
                    'relatedKey' => 'category_id',
                    'createForm' => \Moox\Category\Forms\TaxonomyCreateForm::class,
                    'hierarchical' => true,
                ],
                'tags' => [
                    'label' => 'Tags',
                    'model' => \Moox\Tag\Models\Tag::class,
                    'table' => 'taggables',
                    'relationship' => 'taggable',
                    'foreignKey' => 'taggable_id',
                    'relatedKey' => 'tag_id',
                    'createForm' => \Moox\Tag\Forms\TaxonomyCreateForm::class,
                ],
            ],
        ],
        'simple-item' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//builder::translations.simple-item',
            'plural' => 'trans//builder::translations.simple-items',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Builder table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [],

            /*
            |--------------------------------------------------------------------------
            | Taxonomies
            |--------------------------------------------------------------------------
            |
            | This array contains the taxonomies that should be shown.
            | This is work in progress and not yet fully documented.
            |
            */

            'taxonomies' => [],
        ],
        'simple-taxonomy' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//builder::translations.simple-taxonomy',
            'plural' => 'trans//builder::translations.simple-taxonomies',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Builder table. They are optional, but
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

            /*
            |--------------------------------------------------------------------------
            | Taxonomies
            |--------------------------------------------------------------------------
            |
            | This array contains the taxonomies that should be shown.
            | This is work in progress and not yet fully documented.
            |
            */

            'taxonomies' => [
                'categories' => [
                    'label' => 'Categories',
                    'model' => \Moox\Category\Models\Category::class,
                    'table' => 'categorizables',
                    'relationship' => 'categorizable',
                    'foreignKey' => 'categorizable_id',
                    'relatedKey' => 'category_id',
                    'createForm' => \Moox\Category\Forms\TaxonomyCreateForm::class,
                    'hierarchical' => true,
                ],
                'tags' => [
                    'label' => 'Tags',
                    'model' => \Moox\Tag\Models\Tag::class,
                    'table' => 'taggables',
                    'relationship' => 'taggable',
                    'foreignKey' => 'taggable_id',
                    'relatedKey' => 'tag_id',
                    'createForm' => \Moox\Tag\Forms\TaxonomyCreateForm::class,
                ],
            ],
        ],
        'nested-taxonomy' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//builder::translations.nested-taxonomy',
            'plural' => 'trans//builder::translations.nested-taxonomies',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Builder table. They are optional, but
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

            /*
            |--------------------------------------------------------------------------
            | Taxonomies
            |--------------------------------------------------------------------------
            |
            | This array contains the taxonomies that should be shown.
            | This is work in progress and not yet fully documented.
            |
            */

            'taxonomies' => [
                'categories' => [
                    'label' => 'Categories',
                    'model' => \Moox\Category\Models\Category::class,
                    'table' => 'categorizables',
                    'relationship' => 'categorizable',
                    'foreignKey' => 'categorizable_id',
                    'relatedKey' => 'category_id',
                    'createForm' => \Moox\Category\Forms\TaxonomyCreateForm::class,
                    'hierarchical' => true,
                ],
                'tags' => [
                    'label' => 'Tags',
                    'model' => \Moox\Tag\Models\Tag::class,
                    'table' => 'taggables',
                    'relationship' => 'taggable',
                    'foreignKey' => 'taggable_id',
                    'relatedKey' => 'tag_id',
                    'createForm' => \Moox\Tag\Forms\TaxonomyCreateForm::class,
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Relations
            |--------------------------------------------------------------------------
            |
            | This array contains the relations that should be shown.
            | This is work in progress and not yet fully documented.
            |
            */

            'relations' => [
                'rooms' => [
                    'label' => 'Rooms',
                    'model' => \Moox\Room\Models\Room::class,
                    'table' => 'roomables',
                    'type' => 'has-many',
                    'relationship' => 'roomable',
                    'foreignKey' => 'roomable_id',
                    'relatedKey' => 'room_id',
                    'createForm' => \Moox\Brand\Forms\RelationCreateForm::class,
                    'hierarchical' => true,
                ],
                'bookings' => [
                    'label' => 'Bookings',
                    'model' => \Moox\Booking\Models\Booking::class,
                    'table' => 'bookings',
                    'type' => 'has-many',
                    'relationship' => 'bookable',
                    'foreignKey' => 'bookable_id',
                    'relatedKey' => 'booking_id',
                    'createForm' => \Moox\Tag\Forms\TaxonomyCreateForm::class,
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation Group
    |--------------------------------------------------------------------------
    |
    | The translatable title of the navigation group in the
    | Filament Admin Panel. Instead of a translatable
    | string, you may also use a simple string.
    |
    */

    'navigation_group' => 'trans//builder::translations.builder',

    /*
    |--------------------------------------------------------------------------
    | Navigation Sort
    |--------------------------------------------------------------------------
    |
    | This value is the sort order of the navigation item in the
    | Filament Admin Panel. If you use a bunch of Moox
    | plugins, everything should be in order.
    |
    */

    'navigation_sort' => 9990,

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

    'user_model' => \Moox\User\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Allow Slug Change - WIP
    |--------------------------------------------------------------------------
    |
    | // TODO: Work in progress.
    |
    */

    'allow_slug_change_after_saved' => env('ALLOW_SLUG_CHANGE_AFTER_SAVED', true),
    'allow_slug_change_after_publish' => env('ALLOW_SLUG_CHANGE_AFTER_PUBLISH', false),
];
