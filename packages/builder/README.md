![Moox Builder](https://github.com/mooxphp/moox/raw/main/art/banner/builder.jpg)

# Moox Builder

🚀 What do you want to ~~build~~ **ship** today?

From idea to a working LaravelApp in minutes. No coding required.

## Work-in-Progress

-   ✅ Entity generation with `builder:create`
-   🟡 Generate all available contexts and presets
-   🟡 Entity deletion with `builder:delete`

## Pro Version (WIP, too)

-   🚧 Package generation with `builder:package`
-   🚧 App (Panel) generation with `builder:app`
-   🚧 Frontend generation with `builder:frontend`
-   🚧 UI for creating entities with custom blocks
-   🚧 UI for creating packages
-   🚧 UI for managing apps (panels)
-   🚧 UI for managing frontend (themes)
-   🚧 UI for managing versions
-   🚧 Generate complete packages or apps as zip

## Overview

Moox Builder is a Laravel Package and Filament UI to build complete applications and packages with zero coding:

-   🚀 Generate complete Filament Resources in App or Package context
-   🚀 Generate and Publish Laravel Packages
-   🚀 Preview and test everything instantly
-   🔧 Production-ready code
-   🔧 Dependency free, remove anytime
-   🔧 Fully extensible

## Quick Installation

These two commmands are all you need to install the package:

```bash
composer require moox/builder
php artisan mooxbuilder:install
```

Curious what the install command does? See manual installation below.

## Compatibility

-   Laravel 11
-   PHP 8.3
-   Filament 3.2
-   Livewire 3
-   Tailwind CSS 3
-   PHPStan 2
-   Pest 3

## Quality Assurance

All generated code is:

-   Type-safe with full type declarations
-   PSR-12 compliant via Pint
-   PHPStan Level 8 validated
-   Pest tested
-   Ready for production

## Core Concepts

### Contexts

-   **App**: Direct application integration
-   **Package**: Laravel package generation
-   **Preview**: Instant testing environment
-   Custom contexts via configuration

### Blocks

Building blocks for your entities:

-   **Fields**: Text, Number, Date, Select, etc.
-   **Features**: SoftDelete, Publish, Author
-   **Sections**: Logical field groupings

### Presets

Presets are pre-configured collections of blocks:

-   `Simple Item`: Basic CRUD resource
-   `Publishable Item`: With publishing workflow
-   `Full Item`: All available features
-   `Simple Taxonomy`: For tag-like structures
-   `Nested Taxonomy`: For category-like structures, using nested set
-   Custom presets via configuration

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

### Build Process

1. **Entity Definition**

    - Command/UI initiates build
    - Option to generate from migration
    - Block configuration (or Preset)
    - Context selection

2. **Generation**

    - Creates DB entries for entity and build
    - Generates files from templates and blocks

3. **Integration**

    - Preview functionality
    - Production deployment
    - Package publishing

### Types

Types are currently only partly implemented and only used for the EntityImporter.

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

Take a look at the [Configuration](config/builder.php) for more information.

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

## Do not track Previews

If you want to use the Preview feature, you may add the following to your `.gitignore`:

```
/app/Builder/*
/config/previews/*
/lang/*/previews/*
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

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [Alf Drollinger](https://github.com/adrolli)
-   [All Contributors](../../contributors)

## Contributing

We value every contribution. Moox is developed in the [Moox Monorepo](https://github.com/mooxphp/moox), that uses [All Contributors](https://allcontributors.org/) for managing contributions. Please refer to the Monorepo docs for more information.

## Coding Rules

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

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Roadmap

See [DEVLOG.md](DEVLOG.md) for the current tasks and ideas for the future.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
