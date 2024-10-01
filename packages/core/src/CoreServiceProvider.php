<?php

declare(strict_types=1);

namespace Moox\Core;

use Illuminate\Support\Facades\Gate;
use Moox\Core\Commands\InstallCommand;
use Moox\Core\Traits\GoogleIcons;
use Moox\Core\Traits\TranslatableConfig;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CoreServiceProvider extends PackageServiceProvider
{
    use GoogleIcons, TranslatableConfig;

    public function boot()
    {
        parent::boot();

        // TODO: Uncomment this line to enable policies
        //$this->setPolicies();

        if (config('core.use_google_icons', true)) {
            $this->useGoogleIcons();
        }

        $this->app->booted(function () {
            $this->translateConfigurations();
        });
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('core')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasRoute('api')
            ->hasCommand(InstallCommand::class);
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
        $packages = $this->getPackageNames();

        foreach ($packages as $slug => $name) {
            $configData = config($slug);
            if (is_array($configData)) {
                $translatedConfig = $this->translateConfig($configData);
                config([$slug => $translatedConfig]);
            }
        }
    }

    public function setPolicies()
    {
        $packages = $this->getPackageNames();

        foreach ($packages as $package) {
            if (isset($package['models']) && is_array($package['models'])) {
                foreach ($package['models'] as $model => $settings) {
                    if (isset($settings['policy']) && class_exists($settings['policy'])) {
                        $modelClass = "App\\Models\\$model";
                        if (class_exists($modelClass)) {
                            Gate::policy($modelClass, $settings['policy']);
                        }
                    }
                }
            }
        }

        Gate::guessPolicyNamesUsing(function ($modelClass) {
            return \Moox\Permission\Policies\DefaultPolicy::class;
        });
    }
}
