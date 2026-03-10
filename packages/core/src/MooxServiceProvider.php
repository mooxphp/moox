<?php

namespace Moox\Core;

use Illuminate\Support\Facades\File;
use ReflectionClass;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

abstract class MooxServiceProvider extends PackageServiceProvider
{
    public ?object $mooxPackage = null;

    public function configurePackage(Package $package): void
    {
        $this->configureMoox($package);
    }

    public function getMooxPackage(): object
    {
        if ($this->mooxPackage === null) {
            $packagePath = dirname((new ReflectionClass(static::class))->getFileName());

            $this->mooxPackage = new class($packagePath)
            {
                public string $title;

                public ?array $plugins = null;

                public bool $firstPlugin = false;

                public ?array $requiredSeeders = null;

                public string $packagePath;

                public bool $released = false;

                public string $stability = 'dev';

                public string $category = 'unsorted';

                public ?string $parentTheme = null;

                public array $staticSeeders = [];

                public array $usedFor = [];

                public array $templateFor = [];

                public array $templateReplace = [];

                public array $templateRename = [];

                public array $templateSectionReplace = [];

                public array $templateRemove = [];

                public array $templateEntityFiles = [];

                public array $alternatePackages = [];

                public function __construct(string $packagePath)
                {
                    $this->packagePath = $packagePath;
                }

                public function title(string $title): self
                {
                    $this->title = $title;

                    return $this;
                }

                public function released(bool $released): self
                {
                    $this->released = $released;

                    return $this;
                }

                public function stability(string $stability): self
                {
                    $this->stability = $stability;

                    return $this;
                }

                public function category(string $category): self
                {
                    $this->category = $category;

                    return $this;
                }

                public function mooxPlugins(array $plugins): self
                {
                    $this->plugins = $plugins;

                    return $this;
                }

                public function getMooxPlugins(): array
                {
                    if ($this->plugins !== null) {
                        return $this->plugins;
                    }
                    $ds = DIRECTORY_SEPARATOR;

                    // First, try to get plugins from composer.json extra.moox.install.plugins
                    // packagePath points to src/, so we need to go one level up to get package root
                    $packageRoot = dirname($this->packagePath);
                    $composerPath = $packageRoot.$ds.'composer.json';
                    if (File::exists($composerPath)) {
                        $composer = json_decode(File::get($composerPath), true);
                        $plugins = $composer['extra']['moox']['install']['plugins'] ?? null;
                        if (is_array($plugins) && ! empty($plugins)) {
                            return $plugins;
                        }
                    }
                    // Fallback: Auto-detect plugins from file system

                    // Try multiple possible plugin paths
                    $possiblePaths = [
                        $this->packagePath.$ds.'src'.$ds.'Filament'.$ds.'Plugins',
                        $this->packagePath.$ds.'Filament'.$ds.'Plugins',
                        $this->packagePath.$ds.'src'.$ds.'Moox'.$ds.'Plugins',
                        $this->packagePath.$ds.'Moox'.$ds.'Plugins',
                    ];

                    foreach ($possiblePaths as $pluginPath) {
                        if (is_dir($pluginPath)) {
                            $pluginFiles = glob($pluginPath.$ds.'*Plugin.php') ?: [];
                            if (! empty($pluginFiles)) {
                                // Extract class names from files
                                $plugins = [];
                                foreach ($pluginFiles as $file) {
                                    $content = file_get_contents($file);
                                    if (preg_match('/namespace\s+([^;]+);/', $content, $nsMatch) &&
                                        preg_match('/class\s+(\w+)/', $content, $classMatch)) {
                                        $plugins[] = $nsMatch[1].'\\'.$classMatch[1];
                                    }
                                }

                                return $plugins;
                            }
                        }
                    }

                    return [];
                }

                public function mooxFirstPlugin(bool $isFirst): self
                {
                    $this->firstPlugin = $isFirst;

                    return $this;
                }

                public function isFirstPlugin(): bool
                {
                    return $this->firstPlugin;
                }

                public function parentTheme(string $theme): self
                {
                    $this->parentTheme = $theme;

                    return $this;
                }

                public function getParentTheme(): ?string
                {
                    return $this->parentTheme;
                }

                public function staticSeeders(array $seeders): self
                {
                    $this->staticSeeders = $seeders;

                    return $this;
                }

                public function getStaticSeeders(): array
                {
                    return $this->staticSeeders;
                }

                public function usedFor(array $purposes): self
                {
                    $this->usedFor = $purposes;

                    return $this;
                }

                public function getUsedFor(): array
                {
                    return $this->usedFor;
                }

                public function templateFor(array $purposes): self
                {
                    $this->templateFor = $purposes;

                    return $this;
                }

                public function getTemplateFor(): array
                {
                    return $this->templateFor;
                }

                public function templateReplace(array $replacements): self
                {
                    $this->templateReplace = $replacements;

                    return $this;
                }

                public function getTemplateReplace(): array
                {
                    return $this->templateReplace;
                }

                public function templateRename(array $renames): self
                {
                    $this->templateRename = $renames;

                    return $this;
                }

                public function getTemplateRename(): array
                {
                    return $this->templateRename;
                }

                public function templateSectionReplace(array $replacements): self
                {
                    $this->templateSectionReplace = $replacements;

                    return $this;
                }

                public function getTemplateSectionReplace(): array
                {
                    return $this->templateSectionReplace;
                }

                public function templateRemove(array $files): self
                {
                    $this->templateRemove = $files;

                    return $this;
                }

                public function getTemplateRemove(): array
                {
                    return $this->templateRemove;
                }

                public function templateEntityFiles(array $files): self
                {
                    $this->templateEntityFiles = $files;

                    return $this;
                }

                public function getTemplateEntityFiles(): array
                {
                    return $this->templateEntityFiles;
                }

                public function alternatePackages(array $packages): self
                {
                    $this->alternatePackages = $packages;

                    return $this;
                }

                public function getAlternatePackages(): array
                {
                    return $this->alternatePackages;
                }

                public function mooxRequiredSeeders(array $seeders): self
                {
                    $this->requiredSeeders = $seeders;

                    return $this;
                }

                public function getRequiredSeeders(): array
                {
                    return $this->requiredSeeders ?? [];
                }
            };
        }

        return $this->mooxPackage;
    }

    abstract public function configureMoox(Package $package): void;

    public function register()
    {
        parent::register();
    }

    public function boot(): void
    {
        parent::boot();
        $this->mooxInfo();
    }

    public function mooxInfo(): array
    {
        // Ensure package is initialized (needed when called on fresh instance)
        if (! isset($this->package)) {
            $this->package = new Package;
            $this->configurePackage($this->package);
        }

        $plugins = $this->getMooxPackage()->getMooxPlugins();
        $firstPlugin = $this->getMooxPackage()->isFirstPlugin();

        // Get package root directory (one level up from src/)
        $providerPath = dirname((new ReflectionClass(static::class))->getFileName());
        $packageRoot = dirname($providerPath); // Go up from src/ to package root

        $ds = DIRECTORY_SEPARATOR;

        // Use same names as Spatie: name + shortName (used in publishes() for tags)
        $packageName = $this->package->name ?? null;
        $shortName = $packageName ? $this->package->shortName() : null;

        // Get info directly from Spatie Package object
        $hasConfig = ! empty($this->package->configFileNames ?? []);
        $hasTranslations = $this->package->hasTranslations ?? false;
        $hasMigrations = ! empty($this->package->migrationFileNames ?? []);

        // Migrations from package or filesystem (same source as ProcessMigrations)
        $migrations = $this->package->migrationFileNames ?? [];
        if (empty($migrations)) {
            $migrationPath = $packageRoot.$ds.'database'.$ds.'migrations';
            if (is_dir($migrationPath)) {
                $migrationFiles = array_merge(
                    glob($migrationPath.$ds.'*.php') ?: [],
                    glob($migrationPath.$ds.'*.stub') ?: []
                );
                $migrations = array_map(function (string $migration): string {
                    $name = basename($migration);

                    return str_replace(['.php', '.stub'], '', $name);
                }, $migrationFiles);
            }
        }

        // Seeders from filesystem
        $seeders = [];
        $seederPath = $packageRoot.$ds.'database'.$ds.'seeders';
        if (is_dir($seederPath)) {
            $seederFiles = glob($seederPath.$ds.'*.php') ?: [];
            $seeders = array_map(
                fn (string $seeder): string => basename($seeder, '.php'),
                $seederFiles
            );
        }

        // Config files with source and target paths
        $configFiles = [];
        $configFileNames = $this->package->configFileNames ?? [];

        // If no config names from package, scan filesystem
        if (empty($configFileNames)) {
            $configPath = $packageRoot.$ds.'config';
            if (is_dir($configPath)) {
                $files = glob($configPath.$ds.'*.php') ?: [];
                foreach ($files as $file) {
                    $configFileNames[] = basename($file, '.php');
                }
            }
        }

        // Translations from filesystem
        $translations = [];
        $translationPath = $packageRoot.$ds.'resources'.$ds.'lang';
        if (is_dir($translationPath)) {
            $langDirs = glob($translationPath.$ds.'*', GLOB_ONLYDIR) ?: [];
            foreach ($langDirs as $langDir) {
                $translationFiles = glob($langDir.$ds.'*.php') ?: [];
                foreach ($translationFiles as $file) {
                    $translations[] = basename($file, '.php');
                }
            }
            $translations = array_unique($translations);
        }

        // Publish tags exactly as Spatie registers them (ProcessMigrations, ProcessConfigs, ProcessTranslations)
        // so installer uses the same tag the ServiceProvider used in $this->publishes(..., tag)
        $publishTags = [];
        if ($shortName !== null) {
            $publishTags = [
                'config' => $shortName.'-config',
                'migrations' => $shortName.'-migrations',
                'translations' => $shortName.'-translations',
            ];
        }

        $mooxInfo = [
            'packageName' => $packageName,
            'publishTags' => $publishTags,
            'plugins' => $plugins,
            'firstPlugin' => $firstPlugin,
            'migrations' => $migrations,
            'seeders' => $seeders,
            'configFiles' => $configFiles,
            'translations' => $translations,
        ];

        return $mooxInfo;
    }
}
