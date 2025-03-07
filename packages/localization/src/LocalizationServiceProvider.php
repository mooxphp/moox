<?php

declare(strict_types=1);

namespace Moox\Localization;

use Illuminate\Routing\Router;
use Livewire\Livewire;
use Moox\Localization\Http\Middleware\LanguageMiddleware;
use Moox\Localization\Livewire\LanguageSwitch;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LocalizationServiceProvider extends PackageServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->mergeConfigFiles();

        $this->addMiddlewareToGroup($this->app->make(Router::class));

        $this->registerLivewireComponents();

        $this->registerFilamentPanel();
    }

    public function mergeConfigFiles()
    {
        $configs = [
            'localization' => 'localization/localization',
        ];

        foreach ($configs as $file => $namespace) {
            $this->mergeConfigFrom(__DIR__."/../config/{$file}.php", $namespace);
        }
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('localization')
            ->hasConfigFile(['localization'])
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands()
            ->hasMigration('create_localizations_table');
    }

    public function addMiddlewareToGroup(Router $router)
    {
        $router->pushMiddlewareToGroup('web', LanguageMiddleware::class);
    }

    public function registerLivewireComponents()
    {
        if (class_exists(Livewire::class)) {
            Livewire::component('language-switch', LanguageSwitch::class);
        }
    }

    public function registerFilamentPanel()
    {
        if (config('localization.enable-panel')) {
            $this->app->register(\Moox\Localization\Filament\Providers\LocalizationPanelProvider::class);
        }
    }
}
