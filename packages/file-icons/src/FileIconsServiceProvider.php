<?php

declare(strict_types=1);

namespace Moox\FileIcons;

use BladeUI\Icons\Factory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Route;
use Moox\Core\MooxServiceProvider;
use Moox\FileIcons\Http\Controllers\FileIconController;
use Spatie\LaravelPackageTools\Package;

class FileIconsServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('file-icons')
            ->hasConfigFile();
    }

    public function register(): void
    {
        parent::register();

        $this->callAfterResolving(Factory::class, function (Factory $factory, Container $container) {
            $config = $container->make('config')->get('file-icons', []);

            $factory->add('file-icons', array_merge(['path' => __DIR__.'/../resources/svg'], $config));
        });
    }

    public function packageBooted(): void
    {
        Route::get('/vendor/file-icons/{icon}', FileIconController::class)
            ->where('icon', '[a-zA-Z0-9_-]+\.svg');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/svg' => public_path('vendor/file-icons'),
            ], 'file-icons');
        }
    }
}
