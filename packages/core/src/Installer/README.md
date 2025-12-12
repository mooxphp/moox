# Moox Installer System

A dynamic, configurable, and extensible installer architecture for Moox packages.

## Table of Contents

- [Overview](#overview)
- [Quick Start](#quick-start)
- [Architecture](#architecture)
- [Configuration](#configuration)
- [Command Line Usage](#command-line-usage)
- [Creating Custom Installers](#creating-custom-installers)
- [Extension Traits](#extension-traits)
- [Lifecycle Hooks](#lifecycle-hooks)
- [Standards & Requirements](#standards--requirements)
- [API Reference](#api-reference)

---

## Overview

The Moox Installer System provides a standardized, pluggable way to install package assets including:

- **Migrations** - Database schema changes
- **Configs** - Configuration files
- **Translations** - Language files
- **Seeders** - Database seeders
- **Plugins** - Filament panel plugins

### Key Features

| Feature | Description |
|---------|-------------|
| **Configurable** | Full config file support for all installer options |
| **Extensible** | Add custom installers via traits or registry |
| **Skippable** | Skip/only run specific installers via CLI or config |
| **Hookable** | Lifecycle hooks for before/after installation |
| **Priority-based** | Control installation order via priority settings |
| **Panel-aware** | Special support for Filament panel integration |

---

## Quick Start

### Basic Installation

```bash
# Run the installer with all defaults
php artisan moox:install

# Skip specific installers
php artisan moox:install --skip=seeders --skip=translations

# Only run specific installers
php artisan moox:install --only=migrations --only=plugins

# Force overwrite existing assets
php artisan moox:install --force

# Debug mode - show all package information
php artisan moox:install --debug
```

### Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=core-config
```

Edit `config/moox-installer.php`:

```php
return [
    'installers' => [
        'migrations' => [
            'enabled' => true,
            'priority' => 10,
            'run_after_publish' => true,
        ],
        'plugins' => [
            'enabled' => true,
            'priority' => 100,
            'allow_multiple_panels' => true,
        ],
    ],
    'skip' => ['seeders'], // Always skip seeders
];
```

---

## Architecture

### Directory Structure

```
src/Installer/
├── Contracts/
│   ├── AssetInstallerInterface.php      # Core contract
│   └── PanelAwareInstallerInterface.php # Panel-aware contract
├── Installers/
│   ├── MigrationInstaller.php
│   ├── ConfigInstaller.php
│   ├── TranslationInstaller.php
│   ├── SeederInstaller.php
│   └── PluginInstaller.php
├── Traits/
│   ├── HasCustomInstallers.php
│   ├── HasInstallationHooks.php
│   ├── HasSkippableInstallers.php
│   └── HasConfigurableInstallers.php
├── AbstractAssetInstaller.php           # Base class
├── InstallerRegistry.php                # Central registry
└── README.md
```

### Component Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                     MooxInstallCommand                          │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────────────────┐ │
│  │ HasCustom    │ │ HasSkippable │ │ HasConfigurable          │ │
│  │ Installers   │ │ Installers   │ │ Installers               │ │
│  └──────────────┘ └──────────────┘ └──────────────────────────┘ │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                  HasInstallationHooks                    │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     InstallerRegistry                           │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  Manages: register, unregister, enable, disable, skip   │    │
│  │  Provides: getEnabled(), configure(), only(), except()  │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
                              │
          ┌───────────────────┼───────────────────┐
          ▼                   ▼                   ▼
┌──────────────────┐ ┌──────────────────┐ ┌──────────────────┐
│ MigrationInstaller│ │  ConfigInstaller │ │  PluginInstaller │
│   priority: 10    │ │   priority: 20   │ │   priority: 100  │
└──────────────────┘ └──────────────────┘ └──────────────────┘
          │                   │                   │
          └───────────────────┼───────────────────┘
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                   AbstractAssetInstaller                        │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  Provides: config, selectItems, publishPackageAssets   │    │
│  │            info, note, warning, getPackagePath          │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                   AssetInstallerInterface                       │
│  Required: getType, getLabel, getPriority, isEnabled,          │
│            checkExists, install, getItemsFromMooxInfo,         │
│            hasItemSelection, getConfig, setConfig               │
└─────────────────────────────────────────────────────────────────┘
```

### Flow Diagram

```
┌──────────────────┐
│  moox:install    │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Check Filament   │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Initialize       │
│ Registry         │──► Load config, register defaults, apply skip/only
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Scan Moox        │
│ Providers        │──► Find packages with MooxServiceProvider
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Collect Assets   │
│ from mooxInfo()  │──► Gather migrations, configs, plugins, etc.
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ User selects     │
│ asset types      │──► Multiselect prompt
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ For each type:   │
│ ├─ beforeHook    │
│ ├─ installer.run │
│ └─ afterHook     │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Installation     │
│ Complete         │
└──────────────────┘
```

---

## Configuration

### Full Configuration Reference

```php
// config/moox-installer.php

return [
    /*
    |--------------------------------------------------------------------------
    | Installer Configuration
    |--------------------------------------------------------------------------
    */
    'installers' => [
        'migrations' => [
            'enabled' => true,           // Enable/disable this installer
            'priority' => 10,            // Lower = runs first
            'run_after_publish' => true, // Auto-run migrations after publish
            'skip_existing' => true,     // Skip if migrations exist
            'force' => false,            // Force overwrite
        ],

        'configs' => [
            'enabled' => true,
            'priority' => 20,
            'skip_existing' => true,
            'force' => false,
        ],

        'translations' => [
            'enabled' => true,
            'priority' => 30,
            'skip_existing' => true,
            'force' => false,
        ],

        'seeders' => [
            'enabled' => true,
            'priority' => 50,
            'require_confirmation' => true, // Ask before running
        ],

        'plugins' => [
            'enabled' => true,
            'priority' => 100,
            'allow_multiple_panels' => true, // Allow installing in multiple panels
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Skip Installers
    |--------------------------------------------------------------------------
    | These installers will always be skipped.
    */
    'skip' => [
        // 'seeders',
        // 'translations',
    ],

    /*
    |--------------------------------------------------------------------------
    | Only Installers
    |--------------------------------------------------------------------------
    | If set, ONLY these installers will be available.
    */
    'only' => [
        // 'migrations',
        // 'plugins',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Installers
    |--------------------------------------------------------------------------
    | Register additional installer classes.
    */
    'custom_installers' => [
        // \App\Installers\ThemeInstaller::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Hooks
    |--------------------------------------------------------------------------
    | Callback classes for installation lifecycle.
    */
    'hooks' => [
        'before_install' => null,
        'after_install' => null,
        'before_migrations' => null,
        'after_migrations' => null,
        // ... etc
    ],
];
```

---

## Command Line Usage

### Available Options

| Option | Description | Example |
|--------|-------------|---------|
| `--skip` | Skip specific installer types | `--skip=seeders` |
| `--only` | Only run specific installer types | `--only=migrations` |
| `--force` | Force overwrite existing assets | `--force` |
| `--debug` | Show detailed package information | `--debug` |

### Examples

```bash
# Standard installation with all prompts
php artisan moox:install

# Quick install: only migrations and configs
php artisan moox:install --only=migrations --only=configs

# Full install except seeders
php artisan moox:install --skip=seeders

# Force reinstall everything
php artisan moox:install --force

# Debug mode to see all detected packages
php artisan moox:install --debug
```

---

## Creating Custom Installers

### Step 1: Create the Installer Class

```php
<?php

namespace App\Installers;

use Moox\Core\Installer\AbstractAssetInstaller;

class ThemeInstaller extends AbstractAssetInstaller
{
    /**
     * Unique identifier for this installer.
     */
    public function getType(): string
    {
        return 'themes';
    }

    /**
     * Human-readable label.
     */
    public function getLabel(): string
    {
        return 'Themes';
    }

    /**
     * Default configuration.
     */
    protected function getDefaultConfig(): array
    {
        return array_merge(parent::getDefaultConfig(), [
            'priority' => 90,
            'theme_directory' => resource_path('themes'),
        ]);
    }

    /**
     * Key in mooxInfo() array.
     */
    protected function getMooxInfoKey(): string
    {
        return 'themes';
    }

    /**
     * Check if themes already exist.
     */
    public function checkExists(string $packageName, array $items): bool
    {
        $themeDir = $this->config['theme_directory'];
        
        foreach ($items as $theme) {
            if (is_dir("{$themeDir}/{$theme}")) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Install themes.
     */
    public function install(array $assets): bool
    {
        $selectedAssets = $this->selectItems($assets);

        if (empty($selectedAssets)) {
            $this->note('⏩ No themes selected');
            return true;
        }

        $this->info("📦 Installing {$this->getLabel()}...");

        foreach ($selectedAssets as $asset) {
            $packageName = $asset['package'];
            $themes = $asset['data'] ?? [];

            foreach ($themes as $theme) {
                $this->installTheme($packageName, $theme);
            }
        }

        $this->info('✅ Themes installed successfully');
        
        return true;
    }

    protected function installTheme(string $packageName, string $theme): void
    {
        // Your theme installation logic here
        $this->note("  → Installing theme: {$theme}");
    }
}
```

### Step 2: Register the Installer

**Option A: Via Configuration**

```php
// config/moox-installer.php
'custom_installers' => [
    \App\Installers\ThemeInstaller::class,
],
```

**Option B: Via Trait in Command**

```php
use Moox\Core\Installer\Traits\HasCustomInstallers;

class MyInstallCommand extends Command
{
    use HasCustomInstallers;

    protected function getCustomInstallers(): array
    {
        return [
            new \App\Installers\ThemeInstaller(),
        ];
    }
}
```

**Option C: Programmatically**

```php
use Moox\Core\Installer\InstallerRegistry;

$registry = InstallerRegistry::getInstance();
$registry->register('themes', new ThemeInstaller());
```

### Step 3: Provide Assets in Your Package

In your package's service provider, ensure `mooxInfo()` returns the assets:

```php
// In your MooxServiceProvider subclass

public function mooxInfo(): array
{
    return [
        'plugins' => [...],
        'migrations' => [...],
        'themes' => ['dark-mode', 'light-mode'], // Your custom asset type
    ];
}
```

---

## Extension Traits

### HasCustomInstallers

Add custom installers to your command:

```php
use Moox\Core\Installer\Traits\HasCustomInstallers;

class PackageInstallCommand extends Command
{
    use HasCustomInstallers;

    protected function getCustomInstallers(): array
    {
        return [
            new ThemeInstaller(['priority' => 95]),
            new AssetInstaller(),
        ];
    }

    protected function configureRegistry(InstallerRegistry $registry): void
    {
        // Additional registry configuration
        $registry->configure('migrations', ['run_after_publish' => false]);
    }
}
```

### HasSkippableInstallers

Enable `--skip` and `--only` command options:

```php
use Moox\Core\Installer\Traits\HasSkippableInstallers;

class PackageInstallCommand extends Command
{
    use HasSkippableInstallers;

    protected $signature = 'mypackage:install 
        {--skip=* : Skip specific installers}
        {--only=* : Only run specific installers}';

    public function handle()
    {
        $registry = InstallerRegistry::getInstance();
        $this->applySkipOptions($registry);
        
        // Continue with installation...
    }
}
```

### HasConfigurableInstallers

Load configuration from config files:

```php
use Moox\Core\Installer\Traits\HasConfigurableInstallers;

class PackageInstallCommand extends Command
{
    use HasConfigurableInstallers;

    protected function getInstallerConfig(): array
    {
        // Use package-specific config
        return config('mypackage.installer', []);
    }

    public function handle()
    {
        $registry = $this->buildConfiguredRegistry();
        // Continue...
    }
}
```

### HasInstallationHooks

Add lifecycle hooks to the installation process:

```php
use Moox\Core\Installer\Traits\HasInstallationHooks;

class PackageInstallCommand extends Command
{
    use HasInstallationHooks;

    /**
     * Called before any installation begins.
     */
    protected function beforeInstall(): void
    {
        $this->info('🚀 Starting installation...');
        $this->backupDatabase();
    }

    /**
     * Called after all installations complete.
     */
    protected function afterInstall(): void
    {
        $this->clearCaches();
        $this->info('🎉 Installation complete!');
    }

    /**
     * Called before migrations run.
     */
    protected function beforeMigrations(): void
    {
        $this->info('📦 Preparing database...');
    }

    /**
     * Called after migrations complete.
     */
    protected function afterMigrations(): void
    {
        $this->runCustomMigrations();
    }

    /**
     * Called before plugins are installed.
     */
    protected function beforePlugins(): void
    {
        $this->info('🔌 Installing plugins...');
    }

    /**
     * Called after plugins are installed.
     */
    protected function afterPlugins(): void
    {
        $this->refreshPanelCache();
    }
}
```

---

## Lifecycle Hooks

### Available Hooks

| Hook | When Called |
|------|-------------|
| `beforeInstall()` | Before any installation begins |
| `afterInstall()` | After all installations complete |
| `beforeMigrations()` | Before migrations installer runs |
| `afterMigrations()` | After migrations installer completes |
| `beforeConfigs()` | Before configs installer runs |
| `afterConfigs()` | After configs installer completes |
| `beforeTranslations()` | Before translations installer runs |
| `afterTranslations()` | After translations installer completes |
| `beforeSeeders()` | Before seeders installer runs |
| `afterSeeders()` | After seeders installer completes |
| `beforePlugins()` | Before plugins installer runs |
| `afterPlugins()` | After plugins installer completes |

### Dynamic Hooks

For custom installer types, hooks are automatically called based on the type name:

```php
// For a custom 'themes' installer:
protected function beforeThemes(): void { }
protected function afterThemes(): void { }
```

---

## Standards & Requirements

### Package Requirements

For a package to work with the Moox Installer, it must:

#### 1. Extend MooxServiceProvider

```php
<?php

namespace Moox\MyPackage;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class MyPackageServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('my-package')
            ->hasConfigFile()
            ->hasMigrations()
            ->hasTranslations();
    }
}
```

#### 2. Register in Laravel's Provider System

```php
// composer.json
{
    "extra": {
        "laravel": {
            "providers": [
                "Moox\\MyPackage\\MyPackageServiceProvider"
            ]
        }
    }
}
```

#### 3. Provide mooxInfo() Data

The `mooxInfo()` method is automatically generated by `MooxServiceProvider`, but can be extended:

```php
public function mooxInfo(): array
{
    $info = parent::mooxInfo();
    
    // Add custom asset types
    $info['themes'] = ['dark', 'light'];
    
    return $info;
}
```

### Installer Class Requirements

Custom installers MUST:

#### 1. Implement AssetInstallerInterface

```php
use Moox\Core\Installer\Contracts\AssetInstallerInterface;

class MyInstaller implements AssetInstallerInterface
{
    // All interface methods must be implemented
}
```

#### 2. OR Extend AbstractAssetInstaller (Recommended)

```php
use Moox\Core\Installer\AbstractAssetInstaller;

class MyInstaller extends AbstractAssetInstaller
{
    // Only override what you need
    public function getType(): string { return 'mytype'; }
    public function getLabel(): string { return 'My Type'; }
    public function install(array $assets): bool { /* ... */ }
    public function checkExists(string $packageName, array $items): bool { /* ... */ }
}
```

### Naming Conventions

| Component | Convention | Example |
|-----------|------------|---------|
| Installer Class | `{Type}Installer` | `ThemeInstaller` |
| Type identifier | lowercase, singular | `theme` |
| Label | Title Case | `Themes` |
| mooxInfo key | camelCase or snake_case | `themes` or `configFiles` |
| Config key | snake_case | `my_package.installer` |

### Priority Guidelines

| Range | Use Case |
|-------|----------|
| 1-10 | Critical infrastructure (migrations) |
| 11-30 | Configuration and setup |
| 31-50 | Data and content (translations, seeders) |
| 51-99 | Optional enhancements |
| 100+ | UI/Plugin integration |

---

## API Reference

### InstallerRegistry

```php
use Moox\Core\Installer\InstallerRegistry;

// Get singleton instance
$registry = InstallerRegistry::getInstance();

// Register an installer
$registry->register('themes', new ThemeInstaller());

// Unregister an installer
$registry->unregister('themes');

// Get an installer
$installer = $registry->get('migrations');

// Check if registered
$registry->has('migrations'); // true

// Get all installers
$all = $registry->all();

// Get enabled installers (sorted by priority)
$enabled = $registry->getEnabled();

// Skip an installer
$registry->skip('seeders');

// Skip multiple
$registry->skipMany(['seeders', 'translations']);

// Unskip
$registry->unskip('seeders');

// Enable/Disable
$registry->enable('seeders');
$registry->disable('seeders');

// Configure
$registry->configure('migrations', ['priority' => 5]);
$registry->configureAll(['force' => true]);

// Only run specific types
$registry->only(['migrations', 'plugins']);

// Run all except specific types
$registry->except(['seeders']);

// Get all type names
$types = $registry->types();

// Get all labels
$labels = $registry->labels();

// Create modified copy
$newRegistry = $registry->withOnly(['migrations']);
$newRegistry = $registry->without(['seeders']);

// Reset singleton (for testing)
InstallerRegistry::resetInstance();
```

### AbstractAssetInstaller

```php
use Moox\Core\Installer\AbstractAssetInstaller;

class MyInstaller extends AbstractAssetInstaller
{
    // Required implementations
    public function getType(): string;
    public function getLabel(): string;
    public function checkExists(string $packageName, array $items): bool;
    public function install(array $assets): bool;

    // Optional overrides
    protected function getDefaultConfig(): array;
    protected function getMooxInfoKey(): string;
    public function hasItemSelection(): bool;

    // Available helpers
    protected function selectItems(array $assets): array;
    protected function publishPackageAssets(string $packageName, string $type): bool;
    protected function info(string $message): void;
    protected function note(string $message): void;
    protected function warning(string $message): void;
    protected function getPackagePath(string $packageName): ?string;
}
```

### AssetInstallerInterface

```php
interface AssetInstallerInterface
{
    public function getType(): string;
    public function getLabel(): string;
    public function getPriority(): int;
    public function isEnabled(): bool;
    public function checkExists(string $packageName, array $items): bool;
    public function install(array $assets): bool;
    public function getItemsFromMooxInfo(array $mooxInfo): array;
    public function hasItemSelection(): bool;
    public function getConfig(): array;
    public function setConfig(array $config): void;
}
```

### PanelAwareInstallerInterface

```php
interface PanelAwareInstallerInterface extends AssetInstallerInterface
{
    public function setPanelPath(?string $panelPath): void;
    public function getPanelPath(): ?string;
    public function requiresPanelSelection(): bool;
}
```

---

## Troubleshooting

### Common Issues

**Installer not found**
```
⚠️ No installer found for asset type: mytype
```
→ Ensure your installer is registered with the registry.

**Migrations not running**
→ Check if `run_after_publish` is set to `true` in config.

**Plugins not registering**
→ Verify the panel path is correct and the file is writable.

**Assets already exist**
→ Use `--force` to overwrite, or set `skip_existing: false` in config.

### Debug Mode

Run with `--debug` to see detailed information:

```bash
php artisan moox:install --debug
```

This shows:
- All detected Moox packages
- Provider class names
- Which packages don't have MooxServiceProvider
- Asset counts per package

---

## Contributing

When contributing new installers or features:

1. Follow the naming conventions above
2. Extend `AbstractAssetInstaller` when possible
3. Add appropriate tests
4. Update this README with any new features
5. Ensure backwards compatibility

---

## License

Part of the Moox ecosystem. See the main package for license information.

