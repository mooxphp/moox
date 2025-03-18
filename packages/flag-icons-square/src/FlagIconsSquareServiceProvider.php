<?php

declare(strict_types=1);

namespace Moox\FlagIconsSquare;

use BladeUI\Icons\Factory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

final class FlagIconsSquareServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();

        $this->callAfterResolving(Factory::class, function (Factory $factory, Container $container) {
            $config = $container->make('config')->get('flag-icons-square', []);

            $factory->add('flag-icons-square', array_merge(['path' => __DIR__.'/../resources/svg'], $config));
        });
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/flag-icons-square.php', 'flag-icons-square');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/svg' => public_path('vendor/flag-icons-square'),
            ], 'flag-icons-square');

            $this->publishes([
                __DIR__.'/../config/flag-icons-square.php' => $this->app->configPath('flag-icons-square.php'),
            ], 'flag-icons-square-config');
        }
    }
}
