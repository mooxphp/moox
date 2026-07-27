<?php

declare(strict_types=1);

namespace Moox\Core;

use Illuminate\Support\Facades\Gate;
use Moox\Core\Console\Commands\MooxInstallCommand;
use Moox\Core\Console\Commands\PublishScheduledContentCommand;
use Moox\Core\Console\Commands\ScopesSyncCommand;
use Moox\Core\Services\RelationService;
use Moox\Core\Services\ScopeAssignmentValidator;
use Moox\Core\Services\ScopeRegistry;
use Moox\Core\Services\TabStateManager;
use Moox\Core\Services\TaxonomyService;
use Moox\Core\Traits\HasGoogleIcons;
use Moox\Core\Traits\HasTranslatableConfig;
use Moox\Permission\Policies\DefaultPolicy;
use Spatie\LaravelPackageTools\Package;

class CoreServiceProvider extends MooxServiceProvider
{
    use HasGoogleIcons;
    use HasTranslatableConfig;

    public function packageRegistered(): void
    {
        $this->app->singleton(ScopeRegistry::class);
        $this->app->singleton(ScopeAssignmentValidator::class);
        $this->app->singleton(TabStateManager::class);
        $this->app->singleton(RelationService::class);
        $this->app->singleton(TaxonomyService::class);

        $this->app->booted(function (): void {
            $this->resetTranslatorLoadedGroups();
            $this->translateConfigurations();
        });
    }

    public function packageBooted(): void
    {
        if (config('core.use_google_icons', true)) {
            $this->useGoogleIcons();
        }

        $this->loadTranslationsFrom(lang_path('previews'), 'previews');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->package->basePath('/../public') => public_path('vendor/core'),
            ], 'core-assets');
        }
    }

    public function configureMoox(Package $package): void
    {
        $package
            ->name('core')
            ->hasConfigFile(['core', 'moox-installer'])
            ->hasTranslations()
            ->hasMigration('create_scopes_table')
            ->hasRoutes(['api', 'web'])
            ->hasCommands([MooxInstallCommand::class, PublishScheduledContentCommand::class, ScopesSyncCommand::class]);
    }

    protected function getPackageNames(): array
    {
        $packages = config('core.packages', []);
        $packageNames = [];

        foreach ($packages as $key => $details) {
            $packageNames[$key] = $details['package'] ?? null;
        }

        return $packageNames;
    }

    protected function translateConfigurations(): void
    {
        $configs = config()->all();
        $translatedConfigs = $this->translateConfig($configs);

        foreach ($translatedConfigs as $key => $value) {
            if ($key === 'app') {
                continue;
            }

            config([$key => $value]);
        }
    }

    public function setPolicies(): void
    {
        $packages = $this->getPackageNames();

        foreach ($packages as $package) {
            if (isset($package['models']) && is_array($package['models'])) {
                foreach ($package['models'] as $model => $settings) {
                    if (isset($settings['policy']) && class_exists($settings['policy'])) {
                        $modelClass = 'App\Models\\'.$model;
                        if (class_exists($modelClass)) {
                            Gate::policy($modelClass, $settings['policy']);
                        }
                    }
                }
            }
        }

        if (class_exists(DefaultPolicy::class)) {
            Gate::guessPolicyNamesUsing(fn ($modelClass): string => DefaultPolicy::class);
        }
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

    protected function registerCommand(string $commandClassName): void
    {
        $this->app->bind($commandClassName, function () use ($commandClassName) {
            $command = new $commandClassName;
            $command->setVerbosity(config('core.verbosity_level', 'v'));

            return $command;
        });

        $this->commands([$commandClassName]);
    }
}
