<?php

declare(strict_types=1);

namespace Moox\FileIcons;

use BladeUI\Icons\Factory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

final class FileIconsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();

        $this->callAfterResolving(Factory::class, function (Factory $factory, Container $container) {
            $config = $container->make('config')->get('file-icons', []);

            $factory->add('file-icons', array_merge(['path' => __DIR__.'/../resources/svg'], $config));
        });
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/file-icons.php', 'file-icons');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/svg' => public_path('vendor/file-icons'),
            ], 'file-icons');

            $this->publishes([
                __DIR__.'/../config/file-icons.php' => $this->app->configPath('file-icons.php'),
            ], 'file-icons-config');
        }
    }
}
