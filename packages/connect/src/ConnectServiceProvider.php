<?php

declare(strict_types=1);

namespace Moox\Connect;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ConnectServiceProvider extends PackageServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->mergeConfigFiles();

        if (config('connect.enable-panel')) {
            $this->app->register(\Moox\Connect\Filament\Providers\ConnectPanelProvider::class);
        }
    }

    public function mergeConfigFiles()
    {
        $configs = [
            'connect' => 'connect/connect',
            'api-connection' => 'connect/api-connection',
            'api-log' => 'connect/api-log',
            'api-endpoint' => 'connect/api-endpoint',
        ];

        foreach ($configs as $file => $namespace) {
            $this->mergeConfigFrom(__DIR__."/../config/{$file}.php", $namespace);
        }
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('connect')
            ->hasConfigFile(['connect', 'api-connection', 'api-log', 'api-endpoint'])
            ->hasMigrations([
                'create_api_connections_table',
                'create_api_logs_table',
                'create_api_endpoints_table',
            ])
            ->hasTranslations()
            ->hasViews();
    }
}
