<?php

declare(strict_types=1);

namespace Moox\Core;

use Illuminate\Support\Facades\Gate;
use Moox\Core\Commands\InstallCommand;
use Moox\Core\Traits\GoogleIcons;
use Moox\Core\Traits\TranslatableConfig;
use Moox\Permission\Policies\DefaultPolicy;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CoreServiceProvider extends PackageServiceProvider
{
    use GoogleIcons, TranslatableConfig;

    public function boot()
    {
        parent::boot();

        $this->setPolicies();
        $this->useGoogleIcons();
        $this->translateConfigurations();
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('core')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasCommand(InstallCommand::class);
    }

    protected function translateConfigurations()
    {
        $packages = config('core.packages', []);

        foreach ($packages as $slug => $package) {
            $configData = config($slug);
            if (is_array($configData)) {
                $translatedConfig = $this->translateConfig($configData);
                config([$slug => $translatedConfig]);
            }
        }
    }

    public function setPolicies()
    {
        $packages = config('core.packages', []);

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

        // Set DefaultPolicy as fallback for any model not explicitly registered
        Gate::guessPolicyNamesUsing(function ($modelClass) {
            return \Moox\Permission\Policies\DefaultPolicy::class;
        });
    }
}
