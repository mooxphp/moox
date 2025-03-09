<?php

namespace Moox\Core;

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
            $packagePath = dirname((new \ReflectionClass(static::class))->getFileName());

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

                    $pluginPath = $this->packagePath.'/Filament/Plugins';
                    $pluginFiles = glob($pluginPath.'/*.php');

                    return is_array($pluginFiles)
                        ? array_map(fn (string $file): string => basename($file, '.php'), $pluginFiles)
                        : [];
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
        $plugins = $this->getMooxPackage()->getMooxPlugins();
        $firstPlugin = $this->getMooxPackage()->isFirstPlugin();

        $packagePath = dirname((new \ReflectionClass(static::class))->getFileName());

        $migrations = glob($packagePath.'/database/migrations/*.php');
        $migrations = is_array($migrations) ? array_map(
            fn (string $migration): string => basename($migration, '.php'),
            $migrations
        ) : [];

        $seeders = glob($packagePath.'/database/seeders/*.php');
        $seeders = is_array($seeders) ? array_map(
            fn (string $seeder): string => basename($seeder, '.php'),
            $seeders
        ) : [];

        $configFiles = glob($packagePath.'/config/*.php');
        $configFiles = is_array($configFiles) ? array_map(
            fn (string $configFile): string => basename($configFile, '.php'),
            $configFiles
        ) : [];

        $translations = glob($packagePath.'/resources/lang/en/*.php');
        $translations = is_array($translations) ? array_map(
            fn (string $translation): string => basename($translation, '.php'),
            $translations
        ) : [];

        $mooxInfo = [
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
