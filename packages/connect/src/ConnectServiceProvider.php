<?php

declare(strict_types=1);

namespace Moox\Connect;

use Moox\Connect\Console\PurgeImportRecordsCommand;
use Moox\Connect\Filament\Providers\ConnectPanelProvider;
use Moox\Connect\Models\ApiConnection;
use Moox\Connect\Support\ConnectionHealthChecker;
use Moox\Connect\Support\TransformerRegistry;
use Moox\Connect\Transformers\ArrayTransformer;
use Moox\Connect\Transformers\DateTimeTransformer;
use Moox\Connect\Transformers\JsonTransformer;
use Moox\Connect\Transformers\NumberTransformer;
use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class ConnectServiceProvider extends MooxServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->mergeConfigFiles();

        $this->app->singleton(TransformerRegistry::class, function (): TransformerRegistry {
            $registry = new TransformerRegistry;
            $registry->register(new ArrayTransformer('array'));
            $registry->register(new DateTimeTransformer('datetime'));
            $registry->register(new JsonTransformer('json'));
            $registry->register(new NumberTransformer('number'));

            return $registry;
        });

        if (config('connect.enable-panel')) {
            $this->app->register(ConnectPanelProvider::class);
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

    public function configureMoox(Package $package): void
    {
        $package
            ->name('connect')
            ->hasRoutes(['web'])
            ->hasConfigFile(['connect', 'api-connection', 'api-log', 'api-endpoint'])
            ->hasMigrations([
                'create_api_connections_table',
                'create_api_logs_table',
                'create_api_endpoints_table',
                'create_api_import_records_table',
                'create_api_import_payload_chunks_table',
                'add_options_to_api_connections_table',
            ])
            ->hasCommand(PurgeImportRecordsCommand::class)
            ->hasTranslations()
            ->hasViews();
    }

    public function bootingPackage(): void
    {
        ApiConnection::saved(function (ApiConnection $connection): void {
            if ($connection->status === 'Disabled') {
                return;
            }

            $authType = strtolower((string) $connection->auth_type);

            $baseOrHealthChanged = $connection->wasChanged('base_url') || $connection->wasChanged('health_path');

            // Filament can trigger multiple saves for a single "submit".
            // Only re-run the healthcheck when it can actually change the request.
            if (! $baseOrHealthChanged) {
                if ($connection->wasChanged('auth_type')) {
                    app(ConnectionHealthChecker::class)->check($connection);

                    return;
                }

                // When auth is disabled (`auth_type = None`), changes to auth_credentials/headers should not trigger
                // health requests.
                if ($authType === 'none') {
                    return;
                }

                $authRelatedChanged = $connection->wasChanged('auth_credentials')
                    || $connection->wasChanged('login_method')
                    || $connection->wasChanged('headers');

                if (! $authRelatedChanged) {
                    return;
                }
            }

            app(ConnectionHealthChecker::class)->check($connection);
        });
    }
}
