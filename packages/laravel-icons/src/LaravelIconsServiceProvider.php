<?php

declare(strict_types=1);

namespace Moox\LaravelIcons;

use BladeUI\Icons\Factory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

final class LaravelIconsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();

        $this->callAfterResolving(Factory::class, function (Factory $factory, Container $container) {
            $config = $container->make('config')->get('laravel-icons', []);

            $factory->add('laravel-icons', array_merge(['path' => __DIR__.'/../resources/svg'], $config));
        });
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-icons.php', 'laravel-icons');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/svg' => public_path('vendor/laravel-icons'),
            ], 'laravel-icons');

            $this->publishes([
                __DIR__.'/../config/laravel-icons.php' => $this->app->configPath('laravel-icons.php'),
            ], 'laravel-icons-config');
        }
    }
}
