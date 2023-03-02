<?php

declare(strict_types=1);

namespace Usetall\TalluiFlagsRound;

use BladeUI\Icons\Factory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

final class TalluiFlagsRoundServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();

        $this->callAfterResolving(Factory::class, function (Factory $factory, Container $container) {
            $config = $container->make('config')->get('tallui-flags-round', []);

            $factory->add('tallui-flags-round', array_merge(['path' => __DIR__.'/../resources/svg'], $config));
        });
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tallui-flags-round.php', 'tallui-flags-round');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/svg' => public_path('vendor/tallui-flags-round'),
            ], 'tallui-flags-round');

            $this->publishes([
                __DIR__.'/../config/tallui-flags-round.php' => $this->app->configPath('tallui-flags-round.php'),
            ], 'tallui-flags-round-config');
        }
    }
}
