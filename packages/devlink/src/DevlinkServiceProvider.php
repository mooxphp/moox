<?php

declare(strict_types=1);

namespace Moox\Devlink;

use Illuminate\Support\ServiceProvider;
use Moox\Devlink\Console\Commands\DeployCommand;
use Moox\Devlink\Console\Commands\LinkCommand;
use Moox\Devlink\Console\Commands\StatusCommand;
use Moox\Devlink\Console\Commands\UnlinkCommand;

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
                LinkCommand::class,
                DeployCommand::class,
                UnlinkCommand::class,
                StatusCommand::class,
            ]);
        }
    }
}
