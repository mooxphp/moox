<?php

declare(strict_types=1);

namespace Moox\FlagIconsOrigin;

use BladeUI\Icons\Factory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

final class FlagIconsOriginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();

        $this->callAfterResolving(Factory::class, function (Factory $factory, Container $container) {
            $config = $container->make('config')->get('flag-icons-origin', []);

            $factory->add('flag-icons-origin', array_merge(['path' => __DIR__.'/../resources/svg'], $config));
        });
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/flag-icons-origin.php', 'flag-icons-origin');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/svg' => public_path('vendor/flag-icons-origin'),
            ], 'flag-icons-origin');

            $this->publishes([
                __DIR__.'/../config/flag-icons-origin.php' => $this->app->configPath('flag-icons-origin.php'),
            ], 'flag-icons-origin-config');
        }
    }
}
