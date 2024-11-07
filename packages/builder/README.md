![Moox Builder](https://github.com/mooxphp/moox/raw/main/art/banner/builder.jpg)

# Moox Builder

<!--shortdesc-->

This template is used for generating Moox packages. 

## Work in Progress

Moox Builder was previously a GitHub Template, but currently moves to an installable package that will be able to create Packages and Entities. Entities are Filament resources (migration, model, resource including pages).

Moox Builder has Generators that use Blocks (Filament fields and features that provide actions and other methods) and Templates (PHP stub files with simple markers) to create a file in the correct Namespace, managed by Contexts. 

Moox Builder is prepared for it's next iteration: having an UI. But until then, we can make use of Presets, a combination of Blocks, to simply generate an Entity using CLI: `php artisan builder:create`

Dive into the current state of Builder:

## Configuration

You can change nearly everything, Blocks, Generators, Templates, Presets and Contexts in the Moox Builder configuration.

- Own template files? Just paths in config.
- Don't want Moox Feature Blocks? Mute them.
- Have a nice feature to generate? Create your own Block.
- Want to build into a predefined Path and Namespace? Create your Context.

Just make your changes in the published builder.php config file to create your own Builder:

```php
    /*
    |--------------------------------------------------------------------------
    | Blocks
    |--------------------------------------------------------------------------
    |
    | Define the available blocks that can be used to build resources.
    | Mute existing blocks or add your own blocks as you like.
    |
    */

    'blocks' => [
        'author' => \Moox\Builder\Blocks\Author::class,
        'bool' => \Moox\Builder\Blocks\Bool::class,
        'builder' => \Moox\Builder\Blocks\Builder::class,
        'checkbox-list' => \Moox\Builder\Blocks\CheckboxList::class,
        'color-picker' => \Moox\Builder\Blocks\ColorPicker::class,
        'date' => \Moox\Builder\Blocks\Date::class,
        'date-time' => \Moox\Builder\Blocks\DateTime::class,
        'file-upload' => \Moox\Builder\Blocks\FileUpload::class,
        'hidden' => \Moox\Builder\Blocks\Hidden::class,
        'image' => \Moox\Builder\Blocks\Image::class,
        'key-value' => \Moox\Builder\Blocks\KeyValue::class,
        'markdown-editor' => \Moox\Builder\Blocks\MarkdownEditor::class,
        'multi-select' => \Moox\Builder\Blocks\MultiSelect::class,
        'number' => \Moox\Builder\Blocks\Number::class,
        'publish' => \Moox\Builder\Blocks\Publish::class,
        'radio' => \Moox\Builder\Blocks\Radio::class,
        'relationship' => \Moox\Builder\Blocks\Relationship::class,
        'repeater' => \Moox\Builder\Blocks\Repeater::class,
        'rich-editor' => \Moox\Builder\Blocks\RichEditor::class,
        'select' => \Moox\Builder\Blocks\Select::class,
        'soft-delete' => \Moox\Builder\Blocks\SoftDelete::class,
        'tags-input' => \Moox\Builder\Blocks\TagsInput::class,
        'text' => \Moox\Builder\Blocks\Text::class,
        'textarea' => \Moox\Builder\Blocks\TextArea::class,
        'title-with-slug' => \Moox\Builder\Blocks\TitleWithSlug::class,
        'toggle' => \Moox\Builder\Blocks\Toggle::class,
        'toggle-buttons' => \Moox\Builder\Blocks\ToggleButtons::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Build Contexts
    |--------------------------------------------------------------------------
    |
    | Define the available build contexts and their configurations.
    | Each context can have its own path and namespace settings,
    | template and generator, and also can do migrations.
    |
    */

    'contexts' => [
        'app' => [
            'base_path' => app_path(),
            'base_namespace' => 'App',
            'classes' => [
                'model' => [
                    'path' => '%BasePath%\Models',
                    'namespace' => '%BaseNamespace%\\Models',
                    'template' => __DIR__.'/../src/Templates/Entity/model.php.stub',
                    'generator' => \Moox\Builder\Generators\ModelGenerator::class,
                ],
                'resource' => [
                    'path' => '%BasePath%\Resources',
                    'namespace' => '%BaseNamespace%\\Resources',
                    'template' => __DIR__.'/../src/Templates/Entity/resource.php.stub',
                    'page_templates' => [
                        'List' => __DIR__.'/../src/Templates/Entity/pages/list.php.stub',
                        'Create' => __DIR__.'/../src/Templates/Entity/pages/create.php.stub',
                        'Edit' => __DIR__.'/../src/Templates/Entity/pages/edit.php.stub',
                        'View' => __DIR__.'/../src/Templates/Entity/pages/view.php.stub',
                    ],
                    'generator' => \Moox\Builder\Generators\ResourceGenerator::class,
                ],
                'migration' => [
                    'path' => 'database\migrations',
                    'template' => __DIR__.'/../src/Templates/Entity/migration.php.stub',
                    'generator' => \Moox\Builder\Generators\MigrationGenerator::class,
                ],
                'plugin' => [
                    'path' => '%BasePath%\Filament\Plugins',
                    'namespace' => '%BaseNamespace%\\Filament\\Plugins',
                    'template' => __DIR__.'/../src/Templates/Entity/plugin.php.stub',
                    'generator' => \Moox\Builder\Generators\PluginGenerator::class,
                ],
            ],
        ],
        'package' => [
            'base_path' => '$PackagePath',
            'base_namespace' => '$PackageNamespace',
            'classes' => [
                'model' => [
                    'path' => '%BasePath%\src\Models',
                    'namespace' => '%BaseNamespace%\\Models',
                    'template' => __DIR__.'/../src/Templates/Entity/model.php.stub',
                    'generator' => \Moox\Builder\Generators\ModelGenerator::class,
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
                    'generator' => \Moox\Builder\Generators\ResourceGenerator::class,
                ],
                'migration_stub' => [
                    'path' => '%BasePath%\database\migrations',
                    'template' => __DIR__.'/../src/Templates/Entity/migration.php.stub',
                    'generator' => \Moox\Builder\Generators\MigrationGenerator::class,
                ],
                'plugin' => [
                    'path' => '%BasePath%\src',
                    'namespace' => '%BaseNamespace%',
                    'template' => __DIR__.'/../src/Templates/Entity/plugin.php.stub',
                    'generator' => \Moox\Builder\Generators\PluginGenerator::class,
                ],
            ],
        ],
        'preview' => [
            'base_path' => app_path('Builder'),
            'base_namespace' => 'App\\Builder',
            'classes' => [
                'model' => [
                    'path' => '%BasePath%\Models',
                    'namespace' => '%BaseNamespace%\\Models',
                    'template' => __DIR__.'/../src/Templates/Entity/model.php.stub',
                    'generator' => \Moox\Builder\Generators\ModelGenerator::class,
                ],
                'resource' => [
                    'path' => '%BasePath%\Resources',
                    'namespace' => '%BaseNamespace%\\Resources',
                    'template' => __DIR__.'/../src/Templates/Entity/resource.php.stub',
                    'page_templates' => [
                        'List' => __DIR__.'/../src/Templates/Entity/pages/list.php.stub',
                        'Create' => __DIR__.'/../src/Templates/Entity/pages/create.php.stub',
                        'Edit' => __DIR__.'/../src/Templates/Entity/pages/edit.php.stub',
                        'View' => __DIR__.'/../src/Templates/Entity/pages/view.php.stub',
                    ],
                    'generator' => \Moox\Builder\Generators\ResourceGenerator::class,
                ],
                'migration' => [
                    'path' => 'database\migrations',
                    'template' => __DIR__.'/../src/Templates/Entity/migration.php.stub',
                    'generator' => \Moox\Builder\Generators\MigrationGenerator::class,
                ],
            ],
            'should_migrate' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Presets
    |--------------------------------------------------------------------------
    |
    | Register available presets that can be used to quickly scaffold resources.
    | Each preset key must match the class name in lowercase without 'Preset'.
    |
    */

    'presets' => [
        'simple-item' => [
            'class' => \Moox\Builder\Presets\SimpleItemPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'publishable-item' => [
            'class' => \Moox\Builder\Presets\PublishableItemPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'full-item' => [
            'class' => \Moox\Builder\Presets\FullItemPreset::class,
            'generators' => ['model', 'migration', 'resource', 'factory'],
        ],
        'simple-taxonomy' => [
            'class' => \Moox\Builder\Presets\SimpleTaxonomyPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'nested-taxonomy' => [
            'class' => \Moox\Builder\Presets\NestedTaxonomyPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
    ],
];

```



### Blocks

Blocks are Blueprints for Filament Core fields and Moox Features. They can easily be muted and extended in the config array `blocks`

 - AbstractBlock
	- Autor - Moox Feature
	- Bool
	- Builder
	- CheckboxList
	- ColorPicker
	- Date
	- DateTime
	- FileUpload
	- Hidden
	- Image
	- KeyValue
	- MarkdownEditor
	- MultiSelect
	- Number
	- Publish - Moox Feature
	- Radio
	- Relationship
	- Repeater
	- RichEditor
	- Select
	- SoftDelete - Moox Feature
	- TagsInput
	- Text
	- TextArea
	- TitleWithSlug - Moox Feature
	- Toggle
	- ToggleButtons

### Commands

Commands allow to create a quick Entity using a Preset.

- AbstractBuilderCommand
	- CreateEntityCommand
	- DeleteEntityCommand 

#### Create Entity

This command `builder:create`creates an Entity. If you call it without any parameters, it will ask you some questions. With these parameters you can preset all needed information:

Name your Entity:

```bash
php artisan builder:create Post
# Model will be Post, table will be posts
```

Choose your context:

```bash
php artisan builder:create Post --app
# Generates in App/Filament

php artisan builder:create Post --package
# Asks for the Package

php artisan builder:create Post --preview
# Generates in App/Builder and migrates
# To preview: https://your.test/builder/
```

Choose your preset:

```bash
php artisan builder:create Post --preview --preset=simple-item

php artisan builder:create Post --preview --preset=publishable-item

php artisan builder:create Post --preview --preset=full-item

php artisan builder:create Post --preview --preset=simple-taxonomy

php artisan builder:create Post --preview --preset=nested-taxonomy
```

Generate a package entity:

```bash
php artisan builder:create Post --package=My/Blog --preset=simple-item
# will also work --package=My\Blog
```

#### Delete Entity

To delete an entity including the migration and the migrated tables in the database, you can use the delete command. This command will search for a package in app or preview context or it will ask for a namespace, if it does not find the Entity.

```bash
php artisan builder:delete Post
# Searches and deletes the entity
# Asks to remove the database table
```

You can force the command to just remove everything:

```bash
php artisan builder:delete Post --force
# Searches and deletes the entity
# and the migrated db table
```

And you can specify a namespace to also remove the entity from there:

```bash
php artisan builder:delete --package=My/Blog
# will also work --package=My\Blog
```
### Contexts

Contexts make the switch between App, Package or Preview. You can add own Contexts in the config array `contexts`

 - BuildContext
- ContextFactory

### Generators

Generators combine Blocks and Templates to generate the files. You can implement own Generators and Templates in the `contexts` config array

- AbstractGenerator
	- MigrationGenerator
	- ModelGenerator
	- PluginGenerator
	- ResourceGenerator

### Presets

Presets are packages of Blocks that can be used in the Build commands to quickly scaffold useful Entities. You can mute and add own Presets in the config array `presets`

- AbstractPreset
	- FullItemPreset
	- NestedTaxonomyPreset
	- PublishableItemPreset
	- SimpleItemPreset
	- SimpleTaxonomyPreset

### Services

Services allow to directly access builder functions

- AbstractService
	- EntityFilesRemover
	- EntityGenerator
	- EntityTablesRemover

### Templates

These stub files are used for the generation. You can implement own Generators and Templates in the `contexts` config array

- Entity
  - pages
    - create.php.stub
    - edit.php.stub
    - list.php.stub
    - view.php.stub
  - migration.php.stub
  - model.php.stub
  - plugin.php.stub
  - resource.php.stub
- Package
  - Work-in-Progress, see DEVLOG.md

### Traits

These Traits are used by Builder

- HandlesContentCleanup
- HandlesIndentation

### Types

Types allow to select compatible Fields by a given Type.

- AbstractType
	- ArrayType
	- BooleanType
	- DAteTimeType
	- EnumType
	- FileType
	- ImageType
	- NumericType
	- PasswordType
	- RelationType
	- StringType
	- TextType
	- UrlType



<!--/shortdesc-->

## Quick Installation

These two commmands are all you need to install the package:

```bash
composer require moox/builder
php artisan mooxbuilder:install
```

Curious what the install command does? See manual installation below.

## What it does

<!--whatdoes-->

This Laravel Package Template can be used to create Filament Resources including migration, model, resource and pages.

![Moox Builder Item](https://github.com/mooxphp/moox/raw/main/art/screenshot/builder-item.jpg)

### Using the Template (old Builder Template, deprecated)

1. Go to https://github.com/mooxphp/builder
2. Press the `Use this template` button
3. Create a new repository based on the template
4. Clone the repository locally
5. Run `php build.php`in the repo's directory and follow the steps
    - Author Name (Default: Moox Developer): Your Name
    - Author Email (Default: dev@moox.org): your@mail.com
    - Package Name (Default: Blog Package): Your Package
    - Package Description (Default: This is my package Blog Package)
    - Package Entity (Default: Item): e.g. Post
    - Tablename (Default: items): e.g. posts

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

Instead of using the install-command `php artisan mooxbuilder:install` you are able to install this package manually step by step:

```bash
// Publish and run the migrations:
php artisan vendor:publish --tag="builder-migrations"
php artisan migrate

// Publish the config file with:
php artisan vendor:publish --tag="builder-config"
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
