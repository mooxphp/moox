<?php

declare(strict_types=1);

namespace Moox\FlagIconsRect;

use BladeUI\Icons\Factory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

final class FlagIconsRectServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();

        $this->callAfterResolving(Factory::class, function (Factory $factory, Container $container) {
            $config = $container->make('config')->get('flag-icons-rect', []);

            $factory->add('flag-icons-rect', array_merge(['path' => __DIR__.'/../resources/svg'], $config));
        });
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/flag-icons-rect.php', 'flag-icons-rect');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/svg' => public_path('vendor/flag-icons-rect'),
            ], 'flag-icons-rect');

            $this->publishes([
                __DIR__.'/../config/flag-icons-rect.php' => $this->app->configPath('flag-icons-rect.php'),
            ], 'flag-icons-rect-config');
        }
    }
}
