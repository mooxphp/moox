<?php

declare(strict_types=1);

namespace Moox\Msgraph;

use Illuminate\Contracts\Foundation\Application;
use Moox\Core\MooxServiceProvider;
use Moox\Msgraph\Auth\ConnectionRegistry;
use Moox\Msgraph\Auth\GraphClientFactory;
use Spatie\LaravelPackageTools\Package;

class MsgraphServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('msgraph')
            ->hasConfigFile();

        $this->getMooxPackage()
            ->title('Moox Msgraph')
            ->released(false)
            ->stability('dev')
            ->category('integration')
            ->usedFor([
                'Microsoft Graph API integration with connection registry, client factory, and Graph inbox driver',
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ConnectionRegistry::class, function (Application $app): ConnectionRegistry {
            return new ConnectionRegistry(
                $app['config']->get('msgraph.connections', []),
                $app['config']->get('msgraph.default', 'default'),
            );
        });

        $this->app->singleton(GraphClientFactory::class, function (Application $app): GraphClientFactory {
            return new GraphClientFactory($app->make(ConnectionRegistry::class));
        });
    }
}
