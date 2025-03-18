<?php

declare(strict_types=1);

namespace Moox\FlagIconsCircle;

use BladeUI\Icons\Factory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

final class FlagIconsCircleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();

        $this->callAfterResolving(Factory::class, function (Factory $factory, Container $container) {
            $config = $container->make('config')->get('flag-icons-circle', []);

            $factory->add('flag-icons-circle', array_merge(['path' => __DIR__.'/../resources/svg'], $config));
        });
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/flag-icons-circle.php', 'flag-icons-circle');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/svg' => public_path('vendor/flag-icons-circle'),
            ], 'flag-icons-circle');

            $this->publishes([
                __DIR__.'/../config/flag-icons-circle.php' => $this->app->configPath('flag-icons-circle.php'),
            ], 'flag-icons-circle-config');
        }
    }
}
