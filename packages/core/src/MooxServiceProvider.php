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

    protected function getMooxPackage(): object
    {
        if ($this->mooxPackage === null) {
            $packagePath = dirname((new \ReflectionClass(static::class))->getFileName());

            $this->mooxPackage = new class($packagePath)
            {
                protected ?array $plugins = null;

                protected bool $firstPlugin = false;

                protected ?array $requiredSeeders = null;

                protected string $packagePath;

                public function __construct(string $packagePath)
                {
                    $this->packagePath = $packagePath;
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

    protected function registerCommand(string $commandClassName): void
    {
        $this->app->bind($commandClassName, function () use ($commandClassName) {
            $command = new $commandClassName;
            $command->setVerbosity(env('VERBOSITY_LEVEL', 'v'));

            return $command;
        });

        $this->commands([$commandClassName]);
    }

    public function hasCommand(string $commandClassName): Package
    {
        $this->registerCommand($commandClassName);

        return $this->package;
    }

    public function hasCommands(array $commandClassNames): Package
    {
        foreach ($commandClassNames as $commandClassName) {
            $this->registerCommand($commandClassName);
        }

        return $this->package;
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
