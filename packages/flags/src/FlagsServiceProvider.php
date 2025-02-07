<?php

declare(strict_types=1);

namespace Moox\Flags;

use BladeUI\Icons\Factory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Override;

final class FlagsServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->registerConfig();

        $this->callAfterResolving(Factory::class, function (Factory $factory, Container $container): void {
            $config = $container->make('config')->get('flags', []);

            $factory->add('flags', array_merge(['path' => __DIR__.'/../resources/svg'], $config));
        });
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/flags.php', 'flags');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/svg' => public_path('vendor/flags'),
            ], 'flags');

            $this->publishes([
                __DIR__.'/../config/flags.php' => $this->app->configPath('flags.php'),
            ], 'flags-config');
        }
    }
}
