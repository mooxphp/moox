<?php

declare(strict_types=1);

namespace Moox\Core;

use Illuminate\Support\Facades\Gate;
use Moox\Core\Console\Commands\MooxInstaller;
use Moox\Core\Console\Commands\PackageServiceCommand;
use Moox\Core\Services\TaxonomyService;
use Moox\Core\Traits\GoogleIcons;
use Moox\Core\Traits\TranslatableConfig;
use Moox\Permission\Policies\DefaultPolicy;
use Override;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CoreServiceProvider extends PackageServiceProvider
{
    use GoogleIcons;
    use TranslatableConfig;

    #[Override]
    public function boot(): void
    {
        parent::boot();

        $this->app->singleton(TaxonomyService::class);

        if (config('core.use_google_icons', true)) {
            $this->useGoogleIcons();
        }

        $this->loadTranslationsFrom(lang_path('previews'), 'previews');

        $this->app->booted(function (): void {
            $this->translateConfigurations();
        });
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('core')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasRoutes(['api', 'web'])
            ->hasCommand(MooxInstaller::class)
            ->hasCommand(PackageServiceCommand::class);
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

    protected function translateConfigurations()
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

        Gate::guessPolicyNamesUsing(fn ($modelClass): string => DefaultPolicy::class);
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
            $command->setVerbosity(env('VERBOSITY_LEVEL', 'v'));

            return $command;
        });

        $this->commands([$commandClassName]);
    }
}
