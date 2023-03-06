<?php

declare(strict_types=1);

namespace Usetall\TalluiFlagsSquare;

use BladeUI\Icons\Factory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

final class TalluiFlagsSquareServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();

        $this->callAfterResolving(Factory::class, function (Factory $factory, Container $container) {
            $config = $container->make('config')->get('tallui-flags-square', []);

            $factory->add('tallui-flags-square', array_merge(['path' => __DIR__.'/../resources/svg'], $config));
        });
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tallui-flags-square.php', 'tallui-flags-square');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/svg' => public_path('vendor/tallui-flags-square'),
            ], 'tallui-flags-square');

            $this->publishes([
                __DIR__.'/../config/tallui-flags-square.php' => $this->app->configPath('tallui-flags-square.php'),
            ], 'tallui-flags-square-config');
        }
    }
}
