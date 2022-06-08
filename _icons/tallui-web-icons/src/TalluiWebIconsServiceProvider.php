<?php

declare(strict_types=1);

namespace Usetall\TalluiWebIcons;

use BladeUI\Icons\Factory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

final class TalluiWebIconsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();

        $this->callAfterResolving(Factory::class, function (Factory $factory, Container $container) {
            $config = $container->make('config')->get('tallui-web-icons', []);

            $factory->add('webicons', array_merge(['path' => __DIR__.'/../resources/svg/black'], $config));
        });
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tallui-web-icons.php', 'tallui-web-icons');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/svg' => public_path('vendor/tallui-web-icons'),
            ], 'tallui-web-icons');

            $this->publishes([
                __DIR__.'/../config/tallui-web-icons.php' => $this->app->configPath('tallui-web-icons.php'),
            ], 'tallui-web-icons-config');
        }
    }
}
