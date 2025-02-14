<?php

declare(strict_types=1);

namespace Moox\Devlink;

use Illuminate\Support\ServiceProvider;
use Moox\Devlink\Console\Commands\DeployPackages;
use Moox\Devlink\Console\Commands\LinkPackages;
use Moox\Devlink\Console\Commands\ListPackages;
use Moox\Devlink\Console\Commands\UnlinkPackages;

class DevlinkServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/devlink.php', 'devlink');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/devlink.php' => config_path('devlink.php'),
            ], 'devlink-config');

            $this->commands([
                LinkPackages::class,
                DeployPackages::class,
                UnlinkPackages::class,
                ListPackages::class,
            ]);
        }
    }
}
