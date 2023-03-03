<?php

declare(strict_types=1);

namespace Usetall\TalluiFlagsCircle;

use BladeUI\Icons\Factory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

final class TalluiFlagsCircleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();

        $this->callAfterResolving(Factory::class, function (Factory $factory, Container $container) {
            $config = $container->make('config')->get('tallui-flags-circle', []);

            $factory->add('tallui-flags-circle', array_merge(['path' => __DIR__.'/../resources/svg'], $config));
        });
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tallui-flags-circle.php', 'tallui-flags-circle');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/svg' => public_path('vendor/tallui-flags-circle'),
            ], 'tallui-flags-circle');

            $this->publishes([
                __DIR__.'/../config/tallui-flags-circle.php' => $this->app->configPath('tallui-flags-circle.php'),
            ], 'tallui-flags-circle-config');
        }
    }
}
