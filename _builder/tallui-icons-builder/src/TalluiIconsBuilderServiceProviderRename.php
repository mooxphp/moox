<?php

declare(strict_types=1);

namespace Usetall\TalluiIconsBuilder;

use BladeUI\Icons\Factory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

final class TalluiIconsBuilderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();

        $this->callAfterResolving(Factory::class, function (Factory $factory, Container $container) {
            $config = $container->make('config')->get('tallui-icons-builder', []);

            $factory->add('tallui-icons-builder', array_merge(['path' => __DIR__.'/../resources/svg'], $config));
        });
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tallui-icons-builder.php', 'tallui-icons-builder');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/svg' => public_path('vendor/tallui-icons-builder'),
            ], 'tallui-icons-builder');

            $this->publishes([
                __DIR__.'/../config/tallui-icons-builder.php' => $this->app->configPath('tallui-icons-builder.php'),
            ], 'tallui-icons-builder-config');
        }
    }
}
