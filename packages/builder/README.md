![Moox Builder](https://github.com/mooxphp/moox/raw/main/art/banner/builder.jpg)

# Moox Builder

Generate high-quality Laravel Packages and Filament Resources with zero coding.

## Work-in-Progress

Moox Builder is under heavy development. While building Filament Resources is already working, generating Laravel Packages is not implemented yet. Builder lacks an UI, but the Artisan build command is fully functional using Presets. There are five demo resources provided with Builder that will be removed, when Builder is able to generate them without issues. That might need a couple of days ...

## Features

-   Generate and Publish Laravel Packages
-   Generate Filament Resource with Migration and Model
-   Generate a Migration or generate from Migration
-   Create your own Blocks, Generators, Presets and Templates
-   Dependency free, you can de-install Moox Builder anytime
-   Generate production-ready code, type-safe, Pint-formatted and checked by PHPStan

## Quick Installation

These two commmands are all you need to install the package:

```bash
composer require moox/builder
php artisan mooxbuilder:install
```

Curious what the install command does? See manual installation below.

## Core Concepts

Moox Builder is a Laravel package that generates fully functional Filament resources and complete Laravel packages. It uses a modular architecture with blocks, presets, and services to generate type-safe, tested, and production-ready code.

### Builds & Contexts

A build represents a generated entity in a specific context:

-   **App Context**: Generates directly in your application
-   **Package Context**: Generates in a Laravel package
-   **Preview Context**: Creates a temporary build for testing
-   You can create your own Contexts, see [Configuration](#configuration)

The BuildManager and BuildRecorder services handle the persistence and state management of builds, storing both the entity configuration and generated files.

### Blocks

Blocks are the fundamental building blocks of entities. Each block represents a Filament field or feature:

-   Field Blocks: Text, Number, Date, Select, etc.
-   Feature Blocks: SoftDelete, Publish, Author, etc.
-   Section Blocks: Combines fields to a section

Blocks provide:

-   Migration definitions
-   Model attributes and methods
-   Filament form/table configurations
-   Required traits and interfaces
-   Factories and Tests

### Presets

Presets are pre-configured collections of blocks for common use cases:

-   `Simple Item`: Basic CRUD resource
-   `Publishable Item`: With publishing workflow
-   `Full Item`: All available features
-   `Simple Taxonomy`: For tag-like structures
-   `Nested Taxonomy`: For category-like structures
-   You can create your own Presets, see [Configuration](#configuration)

### Generators

Generators combine Blocks and Templates to generate the files. They are located in the `src/Generators` directory. They are divided into `Entity` and `Package` generators. You can implement own Generators and Templates in the `contexts` config array, see [Configuration](#configuration).

### Templates

Templates are PHP stub files with simple markers to be replaced by the Generator. You can implement own Templates in the `contexts` config array, see [Configuration](#configuration). They are organized in the `src/Templates` directory into `App`, `Entity` and `Package` folders.

### Services

The service layer manages the generation workflow:

**Block Services**

-   `BlockFactory`: Creates block instances from configuration
-   `BlockReconstructor`: Reconstructs blocks from database records

**Build Services**

-   `BuildManager`: Orchestrates the build lifecycle and state transitions
-   `BuildRecorder`: Persists build data and manages build history

**Entity Services**

-   `ContextAwareService`: Base class for entity services to handle context
-   `EntityCreator`: Creates new entities with initial configuration
-   `EntityRebuilder`: Updates existing entities with new blocks/settings
-   `EntityGenerator`: Generates all entity-related files
-   `EntityImporter`: Imports entities from existing migrations
-   `EntityTablesRemover`: Manages database table cleanup

**File Services**

-   `FileManager`: Handles file operations, path normalization, and content formatting

**Migration Services**

-   `MigrationAnalyzer`: Analyzes existing migrations to extract structure
-   `MigrationCreator`: Generates new migrations from entity configuration

**Package Services**

-   This part is not implemented yet

**Preview Services**

-   `PreviewManager`: Manages the preview context

### Types

Types are currently only partly implemented and only used for the EntityImporter.

## Architecture

### Build Lifecycle

1. **Entity Creation**

-   User creates entity via command or UI
-   EntityCreator sets up database records
-   Blocks are configured based on preset or user input
-   Build context (app/package/preview) is determined

2. **Build Process**

-   BuildManager initiates build process
-   EntityGenerator coordinates file generation
-   Each block contributes its code parts
-   Files are generated from templates
-   BuildRecorder persists the build state

3. **Preview & Testing**

-   PreviewManager creates temporary build
-   Preview builds can exist alongside production builds
-   Tables are created directly (no migrations)
-   Resource is available at /builder endpoint
-   Changes can be tested in isolation

4. **Production Deployment**

-   Entity can be built in app or package context
-   Only one production context (app/package) active at a time
-   Migration files are generated
-   All required files are placed in correct locations
-   Previous builds in same context are deactivated

### State Management

1. **Build States**

-   Each context can have one active build
-   Preview builds can coexist with production builds
-   Previous builds are preserved but inactive
-   Build history tracked per context

2. **Context Types**

-   `App`: Direct application integration
-   `Package`: Standalone package generation
-   `Preview`: Temporary testing environment
-   Custom contexts can be added

3. **Version Control**

-   Each build is versioned within its context
-   Files are tracked per build
-   States are preserved for rollback
-   Context-specific build history

## Database Structure

### builder_entities

-   Core entity configuration
-   Package association
-   No context tracking (moved to builds)

### builder_entity_builds

-   Build state management per context
-   One active build per context
-   Generated files tracking
-   Version control
-   Context and state tracking

### builder_entity_blocks

-   Block configurations
-   Entity associations
-   Options and ordering
-   Shared across contexts

### builder_packages

-   Work-in-progress package configuration

### builder_package_versions

-   Version control for packages, also w-i-p

## Usage

### Commands

Commands allow to create a quick Entity using a Preset.

-   AbstractBuilderCommand
    -   CreateEntityCommand
    -   DeleteEntityCommand

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

## Configuration

You can change nearly everything, Blocks, Generators, Templates, Presets and Contexts in the Moox Builder configuration.

This is the default configuration:

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
        'fields' => [
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
            'radio' => \Moox\Builder\Blocks\Radio::class,
            'relationship' => \Moox\Builder\Blocks\Relationship::class,
            'repeater' => \Moox\Builder\Blocks\Repeater::class,
            'rich-editor' => \Moox\Builder\Blocks\RichEditor::class,
            'select' => \Moox\Builder\Blocks\Select::class,
            'tags-input' => \Moox\Builder\Blocks\TagsInput::class,
            'text' => \Moox\Builder\Blocks\Text::class,
            'textarea' => \Moox\Builder\Blocks\TextArea::class,
            'toggle' => \Moox\Builder\Blocks\Toggle::class,
            'toggle-buttons' => \Moox\Builder\Blocks\ToggleButtons::class,
        ],
        'features' => [
            'author' => \Moox\Builder\Blocks\Author::class,
            'publish' => \Moox\Builder\Blocks\Publish::class,
            'soft-delete' => \Moox\Builder\Blocks\SoftDelete::class,
            'title-with-slug' => \Moox\Builder\Blocks\TitleWithSlug::class,
        ],
        'sections' => [
            // TODO: Not implemented yet.
        ],
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
                    'generator' => \Moox\Builder\Generators\Entity\ModelGenerator::class,
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
                    'generator' => \Moox\Builder\Generators\Entity\ResourceGenerator::class,
                ],
                'migration' => [
                    'path' => 'database\migrations',
                    'template' => __DIR__.'/../src/Templates/Entity/migration.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\MigrationGenerator::class,
                ],
                'plugin' => [
                    'path' => '%BasePath%\Filament\Plugins',
                    'namespace' => '%BaseNamespace%\\Filament\\Plugins',
                    'template' => __DIR__.'/../src/Templates/Entity/plugin.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\PluginGenerator::class,
                ],
                'translation' => [
                    'path' => 'lang\entities',
                    'template' => __DIR__.'/../src/Templates/Entity/translation.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\TranslationGenerator::class,
                ],
                'config' => [
                    'path' => 'config\entities',
                    'template' => __DIR__.'/../src/Templates/Entity/config.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\ConfigGenerator::class,
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
        'preview' => [
            'base_path' => app_path('Builder'),
            'base_namespace' => 'App\\Builder',
            'classes' => [
                'model' => [
                    'path' => '%BasePath%\Models',
                    'namespace' => '%BaseNamespace%\\Models',
                    'template' => __DIR__.'/../src/Templates/Entity/model.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\ModelGenerator::class,
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
                    'generator' => \Moox\Builder\Generators\Entity\ResourceGenerator::class,
                ],
                'migration' => [
                    'path' => database_path('migrations'),
                    'template' => __DIR__.'/../src/Templates/Entity/migration.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\MigrationGenerator::class,
                ],
                'translation' => [
                    'path' => 'lang\previews',
                    'template' => __DIR__.'/../src/Templates/Entity/translation.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\TranslationGenerator::class,
                ],
                'config' => [
                    'path' => 'config\previews',
                    'template' => __DIR__.'/../src/Templates/Entity/config.php.stub',
                    'generator' => \Moox\Builder\Generators\Entity\ConfigGenerator::class,
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

    /*
    |--------------------------------------------------------------------------
    | Package Generator
    |--------------------------------------------------------------------------
    |
    | Define the available generators for the package builder and their
    | templates. You can also add your own generators and templates.
    |
    */

    'package_generator' => [
        'archtest' => [
            'template' => __DIR__.'/../src/Templates/package/archtest.php.stub',
            'generator' => \Moox\Builder\Generators\Package\ArchTestGenerator::class,
        ],
        'changelog' => [
            'template' => __DIR__.'/../src/Templates/package/changelog.md.stub',
            'generator' => \Moox\Builder\Generators\Package\ChangelogGenerator::class,
        ],
        'composer' => [
            'template' => __DIR__.'/../src/Templates/package/composer.json.stub',
            'generator' => \Moox\Builder\Generators\Package\ComposerJsonGenerator::class,
        ],
        'config' => [
            'template' => __DIR__.'/../src/Templates/package/config.php.stub',
            'generator' => \Moox\Builder\Generators\Package\ConfigFileGenerator::class,
        ],
        'funding' => [
            'template' => __DIR__.'/../src/Templates/package/funding.yml.stub',
            'generator' => \Moox\Builder\Generators\Package\FundingGenerator::class,
        ],
        'gitignore' => [
            'template' => __DIR__.'/../src/Templates/package/gitignore.stub',
            'generator' => \Moox\Builder\Generators\Package\GitignoreGenerator::class,
        ],
        'install' => [
            'template' => __DIR__.'/../src/Templates/package/install.php.stub',
            'generator' => \Moox\Builder\Generators\Package\InstallGenerator::class,
        ],
        'license' => [
            'template' => __DIR__.'/../src/Templates/package/license.md.stub',
            'generator' => \Moox\Builder\Generators\Package\LicenceGenerator::class,
        ],
        'panelprovider' => [
            'template' => __DIR__.'/../src/Templates/package/panelprovider.php.stub',
            'generator' => \Moox\Builder\Generators\Package\PanelProviderGenerator::class,
        ],
        'pest' => [
            'template' => __DIR__.'/../src/Templates/package/pest.php.stub',
            'generator' => \Moox\Builder\Generators\Package\PestGenerator::class,
        ],
        'readme' => [
            'template' => __DIR__.'/../src/Templates/package/readme.md.stub',
            'generator' => \Moox\Builder\Generators\Package\ReadmeGenerator::class,
        ],
        'security' => [
            'template' => __DIR__.'/../src/Templates/package/security.md.stub',
            'generator' => \Moox\Builder\Generators\Package\SecurityGenerator::class,
        ],
        'serviceprovider' => [
            'template' => __DIR__.'/../src/Templates/package/serviceprovider.php.stub',
            'generator' => \Moox\Builder\Generators\Package\ServiceProviderGenerator::class,
        ],
        'testcase' => [
            'template' => __DIR__.'/../src/Templates/package/testcase.php.stub',
            'generator' => \Moox\Builder\Generators\Package\TestCaseGenerator::class,
        ],
        'translation' => [
            'template' => __DIR__.'/../src/Templates/package/translation.php.stub',
            'generator' => \Moox\Builder\Generators\Package\TranslationGenerator::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Package Activator
    |--------------------------------------------------------------------------
    |
    | To activate a package, we need to require it and run the install
    | command. You can define your own activator if you like.
    |
    */

    'package_activator' => \Moox\Builder\Services\PackageActivator::class,

    /*
    |--------------------------------------------------------------------------
    | Package Publisher
    |--------------------------------------------------------------------------
    |
    | Publishing a package is a multi step process. You can define your own.
    |
    */

    'package_publisher' => [
        'git' => \Moox\Builder\Services\PackageGitPublisher::class,
        'github' => \Moox\Builder\Services\PackageGitHubPublisher::class,
        'packagist' => \Moox\Builder\Services\PackagePackagistPublisher::class,
    ],

    // GitHub API Token
    'github_api_token' => env('BUILDER_GITHUB_API_TOKEN'),

    // Packagist API Token
    'packagist_username' => env('BUILDER_PACKAGIST_USERNAME'),
    'packagist_api_token' => env('BUILDER_PACKAGIST_API_TOKEN'),
```

## Moox Core Features

You can opt-out of any Moox dependency, but these niceties you'll miss then:

#### Tabs and Translation

Moox Core provides Dynamic Tabs and Translatable Config. See the config file for more details, but as a quick example:

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

-   [Alf Drollinger](https://github.com/adrolli)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
