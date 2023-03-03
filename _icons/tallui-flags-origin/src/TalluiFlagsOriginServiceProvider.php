<?php

declare(strict_types=1);

namespace Usetall\TalluiFlagsOrigin;

use BladeUI\Icons\Factory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

final class TalluiFlagsOriginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();

        $this->callAfterResolving(Factory::class, function (Factory $factory, Container $container) {
            $config = $container->make('config')->get('tallui-flags-origin', []);

            $factory->add('tallui-flags-origin', array_merge(['path' => __DIR__.'/../resources/svg'], $config));
        });
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tallui-flags-origin.php', 'tallui-flags-origin');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/svg' => public_path('vendor/tallui-flags-origin'),
            ], 'tallui-flags-origin');

            $this->publishes([
                __DIR__.'/../config/tallui-flags-origin.php' => $this->app->configPath('tallui-flags-origin.php'),
            ], 'tallui-flags-origin-config');
        }
    }
}
