![Moox Builder](https://github.com/mooxphp/moox/raw/main/art/banner/builder.jpg)

# Moox Builder

Generate high-quality Laravel Packages and Filament Resources with zero coding.

## Work-in-Progress

This is the current state of the Builder:

-   Moox Builder is currently a GitHub Template Repository (will be removed) and now working as an installed package (will be the future)
-   The current state is in this branch: https://github.com/mooxphp/moox/tree/feature/tag
-   The `php artisan builder:create`command is working, tested with simple and published item yet
-   A Panel is available to Preview: https://moox.test/builder
-   There are 5 test entities, that will be deleted including config, when builder is able to generate them

## Features

-   Generate and Publish Laravel Packages (not implemented yet)
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

Moox Builder follows strict development guidelines to ensure high-quality, maintainable code:

### Core Rules

1. Single Responsibility

-   Services have ONE responsibility
-   Generators collect files
-   FileManager handles operations
-   BuildContext manages paths

2. Never

-   Use direct file operations
-   Handle paths manually
-   Format files directly
-   Assume types or interfaces

3. Always

-   Use FileManager for files
-   Use BuildContext for paths
-   Validate inputs
-   Document changes

4. Type Safety

-   Full type declarations
-   PHPStan level 8
-   No assumed signatures
-   Validated inputs

5. All services follow these principles:

-   Type-safe implementations
-   Context awareness where needed
-   Clear responsibility boundaries
-   Proper error handling

### Compatibility

-   Laravel 11
-   PHP 8.3
-   Filament 3.2
-   Livewire 3
-   Tailwind CSS 3
-   PHPStan 2
-   Pest 3

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
-   `BuildStateManager`: Tracks and manages build states across contexts
-   `VersionManager`: Handles version control for packages and builds

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

1. **Build Process**

-   Command/UI creates entity
-   BuildManager orchestrates generation
-   FileManager handles files
-   BuildRecorder persists state

2. **Preview & Testing**

-   Isolated preview environment
-   Direct table creation
-   Parallel to production builds

3. **Production**

-   Single active context
-   Migration generation
-   Clean file placement

### Build Process Architecture

The build process follows a strict service hierarchy:

-   **BuildManager**: Orchestrates the build lifecycle

    -   Validates context and entity existence
    -   Manages build state transitions
    -   Coordinates between services

-   **BuildRecorder**: Handles build persistence

    -   Records build data and files
    -   Manages build history
    -   Ensures data integrity

-   **BuildStateManager**: Manages build state

    -   Tracks current build state
    -   Handles context-specific state
    -   Provides state validation

-   **EntityGenerator**: Generates entity files
    -   Coordinates file generation
    -   Manages generator pipeline
    -   Ensures type safety

## Usage

### Commands

Commands allow to create and delete an Entity using a Preset.

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

[See Configuration](CONFIGURATION.md)

## Moox Core Features

You can opt-out of any Moox dependency, but these niceties you'll miss then:

#### Tabs and Translation

Moox Core provides Dynamic Tabs and Translatable Config. See [Moox Core docs](https://github.com/mooxphp/core/blob/main/README.md#dynamic-tabs).

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

## Roadmap

See [DEVLOG.md](DEVLOG.md) for the current tasks and ideas for the future.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [Alf Drollinger](https://github.com/adrolli)
-   [All Contributors](../../contributors)

## Contributing

We value every contribution. Moox is developed in the [Moox Monorepo](https://github.com/mooxphp/moox), that uses [All Contributors](https://allcontributors.org/) for managing contributions. Please refer to the Monorepo docs for more information.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
