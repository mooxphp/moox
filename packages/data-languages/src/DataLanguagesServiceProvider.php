<?php

declare(strict_types=1);

namespace Moox\DataLanguages;

use Illuminate\Routing\Router;
use Spatie\LaravelPackageTools\Package;
use Moox\DataLanguages\Traits\ConfigScannerTrait;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Moox\DataLanguages\Providers\LanguagePanelProvider;
use Moox\DataLanguages\Http\Middleware\LanguageMiddleware;

class DataLanguagesServiceProvider extends PackageServiceProvider
{
    use ConfigScannerTrait;

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFiles();

        $this->app->register(LanguagePanelProvider::class);
    }

    public function mergeConfigFiles()
    {
        $configs = [
            'data-languages' => 'data-languages/data-languages',
            'static-countries-static-currencies' => 'data-languages/static-countries-static-currencies',
            'static-countries-static-timezones' => 'data-languages/static-countries-static-timezones',
            'static-country' => 'data-languages/static-countries',
            'static-currency' => 'data-languages/static-currencies',
            'static-language' => 'data-languages/static-language',
            'static-locale' => 'data-languages/static-locale',
            'static-timezone' => 'data-languages/static-timezones',
        ];

        foreach ($configs as $file => $namespace) {
            $this->mergeConfigFrom(__DIR__."/../config/{$file}.php", $namespace);
        }
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('data-languages')
            ->hasConfigFile(['data-languages', 'static-countries-static-currencies', 'static-countries-static-timezones', 'static-country', 'static-currency', 'static-language', 'static-locale', 'static-timezone'])
            ->hasViews()
            ->hasTranslations()
            ->hasCommands()
            ->discoversMigrations();
    }

    public function bootingPackage()
    {
        $router = $this->app->make(Router::class);

        // Füge die Middleware zur "web"-Middleware-Gruppe hinzu
        $router->pushMiddlewareToGroup('web', LanguageMiddleware::class);
    }
}
