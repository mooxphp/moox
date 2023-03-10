<?php

declare(strict_types=1);

namespace Usetall\TalluiLaravelIcons;

use BladeUI\Icons\Factory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

final class TalluiLaravelIconsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();

        $this->callAfterResolving(Factory::class, function (Factory $factory, Container $container) {
            $config = $container->make('config')->get('tallui-laravel-icons', []);

            $factory->add('tallui-laravel-icons', array_merge(['path' => __DIR__.'/../resources/svg'], $config));
        });
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tallui-laravel-icons.php', 'tallui-laravel-icons');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/svg' => public_path('vendor/tallui-laravel-icons'),
            ], 'tallui-laravel-icons');

            $this->publishes([
                __DIR__.'/../config/tallui-laravel-icons.php' => $this->app->configPath('tallui-laravel-icons.php'),
            ], 'tallui-laravel-icons-config');
        }
    }
}
