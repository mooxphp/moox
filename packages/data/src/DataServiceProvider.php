<?php

declare(strict_types=1);

namespace Moox\Data;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DataServiceProvider extends PackageServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->mergeConfigFiles();

        if (config('data.enable-panel')) {
            $this->app->register(\Moox\Data\Filament\Providers\DataPanelProvider::class);
        }
    }

    public function mergeConfigFiles()
    {
        $configs = [
            'data' => 'data/data',
            'static-countries-static-currencies' => 'data/static-countries-static-currencies',
            'static-countries-static-timezones' => 'data/static-countries-static-timezones',
            'static-country' => 'data/static-countries',
            'static-currency' => 'data/static-currencies',
            'static-language' => 'data/static-language',
            'static-locale' => 'data/static-locale',
            'static-timezone' => 'data/static-timezones',
        ];

        foreach ($configs as $file => $namespace) {
            $this->mergeConfigFrom(__DIR__."/../config/{$file}.php", $namespace);
        }
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('data')
            ->hasConfigFile(['data', 'static-countries-static-currencies', 'static-countries-static-timezones', 'static-country', 'static-currency', 'static-language', 'static-locale', 'static-timezone'])
            ->hasViews()
            ->hasTranslations()
            ->hasCommands()
            ->hasMigrations([
                'create_static_countries_table',
                'create_static_languages_table',
                'create_static_locales_table',
                'create_static_currencies_table',
                'create_static_timezones_table',
                'create_static_countries_static_currencies_table',
                'create_static_country_static_timezones_table',
            ]);
    }
}
