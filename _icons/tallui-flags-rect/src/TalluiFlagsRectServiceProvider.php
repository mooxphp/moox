<?php

declare(strict_types=1);

namespace Usetall\TalluiFlagsRect;

use BladeUI\Icons\Factory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

final class TalluiFlagsRectServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();

        $this->callAfterResolving(Factory::class, function (Factory $factory, Container $container) {
            $config = $container->make('config')->get('tallui-flags-rect', []);

            $factory->add('tallui-flags-rect', array_merge(['path' => __DIR__.'/../resources/svg'], $config));
        });
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tallui-flags-rect.php', 'tallui-flags-rect');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/svg' => public_path('vendor/tallui-flags-rect'),
            ], 'tallui-flags-rect');

            $this->publishes([
                __DIR__.'/../config/tallui-flags-rect.php' => $this->app->configPath('tallui-flags-rect.php'),
            ], 'tallui-flags-rect-config');
        }
    }
}
