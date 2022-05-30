<?php

declare(strict_types=1);

namespace TallUiCore;

use TallUiCore\Components\BladeComponent;
use TallUiCore\Components\LivewireComponent;
use TallUiCore\Console\PublishCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;
use Livewire\Livewire;

final class TallUiCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tallui-core.php', 'tallui-core');

        if ($this->app->runningInConsole()) {
            $this->commands([
                PublishCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        $this->bootResources();
        $this->bootBladeComponents();
        $this->bootLivewireComponents();
        $this->bootDirectives();
        $this->bootPublishing();
    }

    private function bootResources(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tallui-core');
    }

    private function bootBladeComponents(): void
    {
        $this->callAfterResolving(BladeCompiler::class, function (BladeCompiler $blade) {
            $prefix = config('tallui-core.prefix', '');
            $assets = config('tallui-core.assets', []);

            /** @var BladeComponent $component */
            foreach (config('tallui-core.components', []) as $alias => $component) {
                $blade->component($component, $alias, $prefix);

                $this->registerAssets($component, $assets);
            }
        });
    }

    private function bootLivewireComponents(): void
    {
        // Skip if Livewire isn't installed.
        if (! class_exists(Livewire::class)) {
            return;
        }

        $prefix = config('tallui-core.prefix', '');
        $assets = config('tallui-core.assets', []);

        /** @var LivewireComponent $component */
        foreach (config('tallui-core.livewire', []) as $alias => $component) {
            $alias = $prefix ? "$prefix-$alias" : $alias;

            Livewire::component($alias, $component);

            $this->registerAssets($component, $assets);
        }
    }

    private function registerAssets($component, array $assets): void
    {
        foreach ($component::assets() as $asset) {
            $files = (array) ($assets[$asset] ?? []);

            collect($files)->filter(function (string $file) {
                return Str::endsWith($file, '.css');
            })->each(function (string $style) {
                TallUiCore::addStyle($style);
            });

            collect($files)->filter(function (string $file) {
                return Str::endsWith($file, '.js');
            })->each(function (string $script) {
                TallUiCore::addScript($script);
            });
        }
    }

    private function bootDirectives(): void
    {
        Blade::directive('tuiStyles', function (string $expression) {
            return "<?php echo TallUiCore\\TallUiCore::outputStyles($expression); ?>";
        });

        Blade::directive('tuiScripts', function (string $expression) {
            return "<?php echo TallUiCore\\TallUiCore::outputScripts($expression); ?>";
        });
    }

    private function bootPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/tallui-core.php' => $this->app->configPath('tallui-core.php'),
            ], 'tallui-core-config');

            $this->publishes([
                __DIR__.'/../resources/views' => $this->app->resourcePath('views/vendor/tallui-core'),
            ], 'tallui-core-views');
        }
    }
}
